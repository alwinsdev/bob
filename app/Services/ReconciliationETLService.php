<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\ImportRowError;
use App\Models\LockList;
use App\Models\ReconciliationAuditLog;
use App\Models\ReconciliationQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;

/**
 * Reconciliation ETL Service
 *
 * Three-stage matching engine:
 *   Stage 1 — IMS Flow     (Primary):   Email | Phone | Name | DOB+LastName  => must have Department
 *   Stage 2 — HS Flow      (Secondary): Email | Phone+EffectiveDate          => agent/TXN only
 *   Stage 3 — Locklist     (Authority): PolicyID exact match                 => force-overrides everything
 *
 * Priority Merge: IMS > HS > No Update
 * Locklist runs AFTER merge and overrides any result.
 */
class ReconciliationETLService
{
    private const CONTRACT_PATCH_FLAG_VALUES = ['Home Open', 'Home Close'];

    // ── IMS Lookup Maps (O(1) keyed by normalised identifier) ────────────────
    private array $imsByEmail = []; // key: normalised email
    private array $imsByPhone = []; // key: digits-only phone
    private array $imsByFirstLast = []; // key: "fname_lname"
    private array $imsByDobLast = []; // key: "dob_lname" — DOB is only valid WITH last name

    // ── Health Sherpa Lookup Maps ─────────────────────────────────────────────
    private array $hsByEmail = []; // key: normalised email
    private array $hsByPhoneDate = []; // key: phone => [records with effective_date_ts]

    // ── Agency Payee Lookup Map ───────────────────────────────────────────────
    private array $payeeMap = []; // key: normalised department name

    // ── Locklist ──────────────────────────────────────────────────────────────
    private array $lockList = []; // key: policy_id => ['agent_name', 'department', 'payee_name']
    private array $lockListMisses = []; // key: policy_id => true (memoized misses)

    // ═════════════════════════════════════════════════════════════════════════
    // PUBLIC ENTRY POINT
    // ═════════════════════════════════════════════════════════════════════════

    public function processBatch(ImportBatch $batch): void
    {
        $this->resetBatchRuntimeArtifacts($batch);

        $batch->update([
            'status' => 'processing',
            'error_message' => null,
            'output_file_path' => null,
            'total_records' => 0,
            'processed_records' => 0,
            'failed_records' => 0,
            'skipped_records' => 0,
            'contract_patched_records' => 0,
            'ims_matched_records' => 0,
            'hs_matched_records' => 0,
            'locklist_matched_records' => 0,
            'skipped_summary' => null,
            'failure_summary' => null,
        ]);

        try {
            // ── Step A: Initialize Locklist lookup cache ──────────────────────
            $this->initLockListLookup();

            // ── Step B: Build Agency Payee map (required for IMS Payee lookup) ─
            if ($batch->payee_file_path) {
                $this->buildPayeeMap(Storage::disk('local')->path($batch->payee_file_path));
            }

            $hasIms = (bool) $batch->ims_file_path;
            $hasHs = (bool) $batch->health_sherpa_file_path;

            if (!$hasIms && !$hasHs) {
                throw new \Exception('Configuration Error: At least one source file (IMS or Health Sherpa) must be provided.');
            }

            // ── Step C: Build IMS lookup maps ─────────────────────────────────
            if ($hasIms) {
                $imsPath = Storage::disk('local')->path($batch->ims_file_path);
                $this->validateHeaders($imsPath, ['CLIENT_EMAIL', 'CLIENT_PHONE', 'CLIENT_FIRST_NAME', 'CLIENT_LAST_NAME'], 'IMS');
                $this->buildIMSMap($imsPath);
            }

            // ── Step D: Build Health Sherpa lookup maps ────────────────────────
            if ($hasHs) {
                $hsPath = Storage::disk('local')->path($batch->health_sherpa_file_path);
                $this->validateHeaders($hsPath, ['EMAIL', 'PHONE', 'EFFECTIVE_DATE'], 'Health Sherpa');
                $this->buildHealthSherpaMap($hsPath);
            }

            // ── Step E: Validate Carrier file ─────────────────────────────────
            if (!$batch->carrier_file_path) {
                throw new \Exception('System Error: The core Carrier (BOB) file is missing from this run.');
            }

            $carrierPath = Storage::disk('local')->path($batch->carrier_file_path);
            $this->validateHeaders($carrierPath, ['CONTRACT_ID', 'MEMBER_FIRST_NAME', 'MEMBER_LAST_NAME'], 'Carrier (BOB)');

            // ── Step F: Stream, match, and write output ────────────────────────
            $this->streamAndMatchCarrier($carrierPath, $batch);

        } catch (\Exception $e) {
            $batch->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error("[BATCH #{$batch->id}] Reconciliation Engine Failure: " . $e->getMessage());
        }
    }

    /**
     * In-place reanalysis support:
     * remove stale output artifacts and prior computed rows before a new ETL pass.
     */
    private function resetBatchRuntimeArtifacts(ImportBatch $batch): void
    {
        if (!blank($batch->output_file_path) && Storage::disk('local')->exists($batch->output_file_path)) {
            Storage::disk('local')->delete($batch->output_file_path);
        }

        DB::table('reconciliation_queue')
            ->where('import_batch_id', $batch->id)
            ->delete();

        ImportRowError::query()
            ->where('import_batch_id', $batch->id)
            ->delete();
    }

    /**
     * Enterprise Contract Patch Engine
     *
     * Process a contract correction file against the current batch using
     * the PREVIOUS Final BOB as the authoritative source for flag decisions.
     *
     * Priority: Lock List > Contract Patch > IMS > Health Sherpa
     *
     * Processing Tiers:
     *   1. Not in current batch   → Skip
     *   2. No historical record   → Skip
     *   3. No flag history        → Skip (unless ALLOW_FORCE_PATCH)
     *   4. Already patched        → Skip (idempotency)
     *   5. Locked by LockList     → Skip
     *   6. Valid                  → Apply patch + Audit log
     */
    public function processContractPatch(ImportBatch $batch): void
    {
        $batch->update([
            'status' => 'processing',
            'error_message' => null,
            'output_file_path' => null,
            'total_records' => 0,
            'processed_records' => 0,
            'failed_records' => 0,
            'skipped_records' => 0,
            'contract_patched_records' => 0,
        ]);

        try {
            if (!$batch->contract_file_path) {
                throw new \Exception('System Error: Contract file is missing for this contract patch run.');
            }

            $contractPath = Storage::disk('local')->path($batch->contract_file_path);
            if (!file_exists($contractPath)) {
                throw new \Exception('System Error: Contract file was not found on disk.');
            }

            $columnMap = $this->detectContractPatchHeaders($contractPath);
            $this->streamContractPatch($contractPath, $batch, $columnMap);

        } catch (\Exception $e) {
            $batch->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error("[ContractPatch][Batch {$batch->id}] Processing failed: " . $e->getMessage());
        }
    }

    /**
     * Core enterprise streaming processor.
     *
     * Builds three O(1) lookup maps before the row loop:
     *   - $currentBatchMap  : contract_id → {status, is_patched, queue_record_id, …}
     *   - $historyMap       : contract_id → {status, flag_value, …}  (previous batch)
     *   - $lockListMap      : contract_id → true (LockList by policy_id)
     */
    private function streamContractPatch(string $contractPath, ImportBatch $batch, array $mapping): void
    {
        $parentBatchId = (string) ($batch->parent_batch_id ?? '');
        $allowForcePatch = (bool) config('reconciliation.allow_force_patch', true);

        if ($parentBatchId === '') {
            throw new \Exception('System Error: Contract patch run is not linked to a Final BOB parent batch.');
        }

        // ── Pre-count rows for accurate progress reporting ────────────────────
        $batch->update(['status' => 'processing']);
        try {
            $totalRows = SimpleExcelReader::create($contractPath)->headerOnRow(0)->getRows()->count();
        } catch (\Exception) {
            $totalRows = 0;
        }
        $batch->update(['total_records' => $totalRows]);

        // ── Resolve the previous batch (source of truth for flags) ────────────
        $previousBatchId = $this->resolvePreviousBatchId($batch, $parentBatchId);
        if ($previousBatchId !== '' && $previousBatchId !== $batch->id) {
            $batch->update(['previous_batch_id' => $previousBatchId]);
        }

        // ═════════════════════════════════════════════════════════════════════
        // BUILD O(1) LOOKUP MAPS
        // ═════════════════════════════════════════════════════════════════════

        // Map 1: Current batch — what records exist and their patch state
        $currentBatchMap = [];
        ReconciliationQueue::query()
            ->where('import_batch_id', $parentBatchId)
            ->select([
                'id',
                'transaction_id',
                'contract_id',
                'status',
                'is_patched',
                'locked_by',
                'flag_value',
                'aligned_agent_code',
                'aligned_agent_name',
                'group_team_sales',
                'payee_name',
                'match_method'
            ])
            ->get()
            ->each(function (ReconciliationQueue $r) use (&$currentBatchMap) {
                $key = strtolower(trim((string) $r->contract_id));
                $currentBatchMap[$key][] = $r;
            });

        // Map 2: Historical batch — flag decisions from previous Final BOB
        $historyMap = [];
        if ($previousBatchId !== '') {
            ReconciliationQueue::query()
                ->where('import_batch_id', $previousBatchId)
                ->select(['contract_id', 'status', 'flag_value'])
                ->get()
                ->each(function (ReconciliationQueue $r) use (&$historyMap) {
                    $key = strtolower(trim((string) $r->contract_id));
                    // Keep the most critical (flagged) entry per contract ID
                    if (!isset($historyMap[$key]) || $r->status === 'flagged') {
                        $historyMap[$key] = $r;
                    }
                });
        }

        // Map 3: Lock List — contract IDs that must never be overridden
        $lockListMap = [];
        LockList::query()
            ->select(['policy_id'])
            ->get()
            ->each(function (LockList $entry) use (&$lockListMap) {
                $key = strtolower(trim((string) $entry->policy_id));
                $lockListMap[$key] = true;
            });

        // ═════════════════════════════════════════════════════════════════════
        // PROCESSING STATE
        // ═════════════════════════════════════════════════════════════════════

        $processedRows = 0;
        $patchedRecords = 0;
        $skippedRecords = 0;
        $failedRows = 0;
        $skipReasons = [];          // Aggregated skip reason counters
        $failureReasons = [];          // Aggregated failure reason counters
        $auditLogsBuffer = [];         // Buffered audit inserts
        $queueUpdateBuffer = [];       // Buffered queue DB updates

        $realtimeFlushInterval = max(1, (int) config('reconciliation.patch_realtime_flush_interval', 50));
        $lastRealtimeFlushedAt = microtime(true);

        [$writer, $outputPath] = $this->initContractPatchExcelWriter($batch);

        try {
            DB::beginTransaction();
            $this->writeContractPatchHeader($writer);

            SimpleExcelReader::create($contractPath)->headerOnRow(0)->getRows()->each(
                function (array $row) use ($batch, $mapping, $writer, $parentBatchId, $previousBatchId, $totalRows, $allowForcePatch, &$currentBatchMap, &$historyMap, &$lockListMap, &$processedRows, &$patchedRecords, &$skippedRecords, &$failedRows, &$skipReasons, &$failureReasons, &$auditLogsBuffer, &$queueUpdateBuffer, &$lastRealtimeFlushedAt, &$realtimeFlushInterval) {
                    $processedRows++;
                    $rowNumber = $processedRows;

                    try {
                        // ── Extract Contract ID ───────────────────────────────
                        $contractIdRaw = (string) ($row[$mapping['contract_id']] ?? '');
                        $contractId = $this->normalizePatchId($contractIdRaw);

                        if ($contractId === '') {
                            $failedRows++;
                            $failureReasons['Missing Contract ID'] = ($failureReasons['Missing Contract ID'] ?? 0) + 1;
                            $this->writeContractPatchRow($writer, [
                                'contract_id' => $contractIdRaw,
                                'status' => 'FAILED',
                                'reason' => 'Invalid Format: Missing or empty Contract ID.',
                            ]);
                            return; // next row
                        }

                        $lookupKey = strtolower($contractId);

                        // ── Extract patch values from the file ────────────────
                        $incomingAgentName = $this->extractContractPatchValue($row, $mapping['agent_name'] ?? null);
                        $incomingAgentCode = $this->extractContractPatchValue($row, $mapping['agent_code'] ?? null);
                        $incomingDepartment = $this->extractContractPatchValue($row, $mapping['department'] ?? null);
                        $incomingPayeeName = $this->extractContractPatchValue($row, $mapping['payee_name'] ?? null);

                        if (
                            $incomingAgentName === '' && $incomingAgentCode === ''
                            && $incomingDepartment === '' && $incomingPayeeName === ''
                        ) {
                            $failedRows++;
                            $failureReasons['No Updatable Values'] = ($failureReasons['No Updatable Values'] ?? 0) + 1;
                            $this->writeContractPatchRow($writer, [
                                'contract_id' => $contractId,
                                'status' => 'FAILED',
                                'reason' => 'Invalid Format: Row contains no updatable values.',
                            ]);
                            return;
                        }

                        // ═════════════════════════════════════════════════════
                        // TIER 1: Not in current batch
                        // ═════════════════════════════════════════════════════
                        if (empty($currentBatchMap[$lookupKey])) {
                            $skippedRecords++;
                            $skipReasons['Not in Current Batch'] = ($skipReasons['Not in Current Batch'] ?? 0) + 1;
                            $this->writeContractPatchRow($writer, [
                                'contract_id' => $contractId,
                                'status' => 'SKIPPED',
                                'reason' => 'Not in Current Batch',
                            ]);
                            return;
                        }

                        // ═════════════════════════════════════════════════════
                        // TIER 2: No historical record in previous Final BOB
                        // ═════════════════════════════════════════════════════
                        $historyRecord = $historyMap[$lookupKey] ?? null;
                        if ($previousBatchId !== '' && $historyRecord === null && !$allowForcePatch) {
                            $skippedRecords++;
                            $skipReasons['No Historical Record'] = ($skipReasons['No Historical Record'] ?? 0) + 1;
                            $this->writeContractPatchRow($writer, [
                                'contract_id' => $contractId,
                                'status' => 'SKIPPED',
                                'reason' => 'No Historical Record',
                            ]);
                            return;
                        }

                        // ═════════════════════════════════════════════════════
                        // TIER 3: No flag history (unless force-patch enabled)
                        // ═════════════════════════════════════════════════════
                        $historicallyFlagged = $historyRecord && $historyRecord->status === 'flagged'
                            && in_array($historyRecord->flag_value, self::CONTRACT_PATCH_FLAG_VALUES, true);

                        if (!$historicallyFlagged && !$allowForcePatch) {
                            $skippedRecords++;
                            $skipReasons['Policy Skip (No Flag History)'] = ($skipReasons['Policy Skip (No Flag History)'] ?? 0) + 1;
                            $this->writeContractPatchRow($writer, [
                                'contract_id' => $contractId,
                                'status' => 'SKIPPED',
                                'reason' => 'Policy Skip (No Flag History)',
                                'flag_context' => $historyRecord?->flag_value ?? '',
                            ]);
                            return;
                        }

                        // ═════════════════════════════════════════════════════
                        // TIER 5: Locked by Lock List (checked before TIER 4
                        //         so LockList always wins)
                        // ═════════════════════════════════════════════════════
                        if (!empty($lockListMap[$lookupKey])) {
                            $skippedRecords++;
                            $skipReasons['Locked by LockList'] = ($skipReasons['Locked by LockList'] ?? 0) + 1;
                            $this->writeContractPatchRow($writer, [
                                'contract_id' => $contractId,
                                'status' => 'SKIPPED',
                                'reason' => 'Locked by LockList',
                            ]);
                            return;
                        }

                        // ── Process each matching queue record ────────────────
                        $targets = $currentBatchMap[$lookupKey];

                        foreach ($targets as $target) {
                            // ═════════════════════════════════════════════════
                            // TIER 4: Already patched (idempotency guard)
                            // ═════════════════════════════════════════════════
                            if ($target->is_patched) {
                                $skippedRecords++;
                                $skipReasons['Already Patched'] = ($skipReasons['Already Patched'] ?? 0) + 1;
                                $this->writeContractPatchRow($writer, [
                                    'contract_id' => $contractId,
                                    'status' => 'SKIPPED',
                                    'reason' => 'Already Patched',
                                    'old_agent_name' => (string) ($target->aligned_agent_name ?? ''),
                                    'old_payee_name' => (string) ($target->payee_name ?? ''),
                                    'old_match_source' => (string) ($target->match_method ?? ''),
                                ]);
                                continue;
                            }

                            // ═════════════════════════════════════════════════
                            // TIER 4.5: Locked by an Analyst
                            // ═════════════════════════════════════════════════
                            if ($target->locked_by && $target->locked_by !== $batch->uploaded_by) {
                                $skippedRecords++;
                                $skipReasons['Locked by Analyst'] = ($skipReasons['Locked by Analyst'] ?? 0) + 1;
                                $this->writeContractPatchRow($writer, [
                                    'contract_id' => $contractId,
                                    'status' => 'SKIPPED',
                                    'reason' => 'Locked by another Analyst',
                                    'old_agent_name' => (string) ($target->aligned_agent_name ?? ''),
                                    'old_payee_name' => (string) ($target->payee_name ?? ''),
                                    'old_match_source' => (string) ($target->match_method ?? ''),
                                ]);
                                continue;
                            }

                            // ═════════════════════════════════════════════════
                            // TIER 6: APPLY PATCH
                            // ═════════════════════════════════════════════════
                            $oldAgentCode = (string) ($target->aligned_agent_code ?? '');
                            $oldAgentName = (string) ($target->aligned_agent_name ?? '');
                            $oldDepartment = (string) ($target->group_team_sales ?? '');
                            $oldPayeeName = (string) ($target->payee_name ?? '');
                            $oldMatchSource = (string) ($target->match_method ?? '');

                            $newAgentCode = $incomingAgentCode !== '' ? $incomingAgentCode : $oldAgentCode;
                            $newAgentName = $incomingAgentName !== '' ? $incomingAgentName : $oldAgentName;
                            $newDepartment = $incomingDepartment !== '' ? $incomingDepartment : $oldDepartment;
                            $newPayeeName = $incomingPayeeName !== '' ? $incomingPayeeName : $oldPayeeName;

                            // Buffer DB update (flushed in chunks)
                            $queueUpdateBuffer[] = [
                                'id' => $target->id,
                                'transaction_id' => $target->transaction_id,
                                'import_batch_id' => $parentBatchId,
                                'status' => 'resolved',
                                'match_method' => 'Contract Patch',
                                'aligned_agent_code' => $newAgentCode !== '' ? $newAgentCode : null,
                                'aligned_agent_name' => $newAgentName,
                                'group_team_sales' => $newDepartment,
                                'payee_name' => $newPayeeName,
                                'flag_value' => $historyRecord?->flag_value ?? $target->flag_value,
                                'is_patched' => true,
                                'resolved_by' => $batch->uploaded_by,
                                'resolved_at' => now(),
                                'updated_at' => now(),
                            ];

                            // Buffer audit log entry
                            $auditLogsBuffer[] = [
                                'id' => (string) \Illuminate\Support\Str::ulid(),
                                'contract_id' => $contractId,
                                'batch_id' => $batch->id,
                                'parent_batch_id' => $parentBatchId,
                                'previous_batch_id' => $previousBatchId !== '' ? $previousBatchId : null,
                                'old_agent_code' => $oldAgentCode !== '' ? $oldAgentCode : null,
                                'old_agent_name' => $oldAgentName !== '' ? $oldAgentName : null,
                                'new_agent_code' => $newAgentCode !== '' ? $newAgentCode : null,
                                'new_agent_name' => $newAgentName !== '' ? $newAgentName : null,
                                'old_payee_name' => $oldPayeeName !== '' ? $oldPayeeName : null,
                                'new_payee_name' => $newPayeeName !== '' ? $newPayeeName : null,
                                'old_department' => $oldDepartment !== '' ? $oldDepartment : null,
                                'new_department' => $newDepartment !== '' ? $newDepartment : null,
                                'old_match_source' => $oldMatchSource !== '' ? $oldMatchSource : null,
                                'new_match_source' => 'Contract Patch',
                                'flag_value' => $historyRecord?->flag_value ?? $target->flag_value,
                                'change_type' => 'patch_applied',
                                'updated_by' => $batch->uploaded_by,
                                'queue_record_id' => $target->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            // Write output row
                            $this->writeContractPatchRow($writer, [
                                'contract_id' => $contractId,
                                'status' => 'PATCHED',
                                'reason' => $historicallyFlagged
                                    ? "Flag: {$historyRecord->flag_value}"
                                    : 'Force Patch Applied',
                                'old_agent_name' => $oldAgentName,
                                'new_agent_name' => $newAgentName,
                                'old_payee_name' => $oldPayeeName,
                                'new_payee_name' => $newPayeeName,
                                'old_match_source' => $oldMatchSource,
                                'new_match_source' => 'Contract Patch',
                                'source' => 'Contract Patch',
                            ]);

                            $patchedRecords++;

                            // Flush in chunks for memory efficiency and configurable concurrency
                            if (count($queueUpdateBuffer) >= config('reconciliation.patch_chunk_size', 500)) {
                                $this->flushPatchBuffers($queueUpdateBuffer, $auditLogsBuffer);
                            }
                        }

                    } catch (\Throwable $e) {
                        $failedRows++;
                        $failureReasons['System Exception'] = ($failureReasons['System Exception'] ?? 0) + 1;
                        Log::warning("[ContractPatch][Batch {$batch->id}] Row {$rowNumber} error: " . $e->getMessage());
                        $this->writeContractPatchRow($writer, [
                            'contract_id' => $contractIdRaw ?? '',
                            'status' => 'FAILED',
                            'reason' => 'A system error occurred while processing this row.',
                        ]);
                    }

                    // ── Real-time progress flush (config rows or 2 seconds) ──
                    $shouldFlush = $processedRows <= $realtimeFlushInterval
                        || ($processedRows % $realtimeFlushInterval === 0)
                        || ((microtime(true) - $lastRealtimeFlushedAt) >= 2.0);

                    if ($shouldFlush) {
                        $progress = $totalRows > 0
                            ? min(98, (int) round(($processedRows / $totalRows) * 100))
                            : 10;

                        $batch->update([
                            'processed_records' => $processedRows,
                            'failed_records' => $failedRows,
                            'skipped_records' => $skippedRecords,
                            'contract_patched_records' => $patchedRecords,
                            'status_label' => "Processing: {$processedRows}"
                                . ($totalRows > 0 ? "/{$totalRows}" : ''),
                            'progress_pct' => $progress,
                        ]);
                        $lastRealtimeFlushedAt = microtime(true);
                    }
                }
            );

            // Final buffer flush
            if (!empty($queueUpdateBuffer)) {
                $this->flushPatchBuffers($queueUpdateBuffer, $auditLogsBuffer);
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("[ContractPatch][Batch {$batch->id}] Stream failure: " . $e->getMessage());
            throw $e;
        } finally {
            $writer->close();
        }

        // ── Determine final status ────────────────────────────────────────────
        $status = match (true) {
            $failedRows > 0 && $patchedRecords === 0 && $skippedRecords === 0 => 'failed',
            $failedRows > 0 => 'completed_with_errors',
            $patchedRecords === 0 && $skippedRecords === 0 => 'failed', // Nothing processed, nothing skipped, nothing failed?
            default => 'completed',
        };

        $errorSummary = $this->buildSkipReasonSummary($skipReasons, $patchedRecords, $failedRows, $skippedRecords);

        $batch->update([
            'total_records' => $totalRows,
            'processed_records' => $processedRows,
            'failed_records' => $failedRows,
            'skipped_records' => $skippedRecords,
            'contract_patched_records' => $patchedRecords,
            'skipped_summary' => $skipReasons,
            'failure_summary' => $failureReasons,
            'output_file_path' => $outputPath,
            'status' => $status,
            'status_label' => match ($status) {
                'completed' => 'Completed',
                'completed_with_errors' => 'Partial',
                default => 'Failed',
            },
            'progress_pct' => 100,
            'error_message' => $errorSummary,
        ]);

        Log::info("[ContractPatch][Batch {$batch->id}] Complete — Patched: {$patchedRecords}, Skipped: {$skippedRecords}, Failed: {$failedRows}");
    }

    /**
     * Flush buffered queue updates and audit log inserts to the database.
     * Uses bulk upsert for optimal database scalability.
     */
    private function flushPatchBuffers(array &$queueBuffer, array &$auditBuffer): void
    {
        if (!empty($queueBuffer)) {
            DB::table('reconciliation_queue')->upsert(
                $queueBuffer,
                ['id'], // unique columns
                [       // update columns
                    'import_batch_id',
                    'status',
                    'match_method',
                    'aligned_agent_code',
                    'aligned_agent_name',
                    'group_team_sales',
                    'payee_name',
                    'flag_value',
                    'is_patched',
                    'resolved_by',
                    'resolved_at',
                    'updated_at'
                ]
            );
        }

        if (!empty($auditBuffer)) {
            DB::table('contract_patch_logs')->insert($auditBuffer);
        }

        $queueBuffer = [];
        $auditBuffer = [];
    }

    /**
     * Resolve the best previous-batch ID to use as historical truth.
     *
     * Strategy: find the most recent completed standard batch that predates
     * the current patch run and is not the parent batch itself.
     */
    private function resolvePreviousBatchId(ImportBatch $batch, string $parentBatchId): string
    {
        // If already set on the model, trust it
        if (!empty($batch->previous_batch_id)) {
            return (string) $batch->previous_batch_id;
        }

        // Find the most recent completed standard batch before the parent
        $previous = ImportBatch::query()
            ->where('batch_type', 'standard')
            ->whereIn('status', ['completed', 'completed_with_errors'])
            ->where('id', '!=', $parentBatchId)
            ->orderBy('created_at', 'desc')
            ->value('id');

        return (string) ($previous ?? '');
    }

    /**
     * Build a human-readable summary of skip reasons for the error_message field.
     */
    private function buildSkipReasonSummary(
        array $skipReasons,
        int $patched,
        int $failed,
        int $skipped
    ): ?string {
        if ($patched > 0 && $failed === 0 && $skipped === 0) {
            return null;
        }

        $parts = [];

        if ($patched === 0 && $skipped > 0) {
            $parts[] = "Contract patch did not update any rows.";
        }

        if (!empty($skipReasons)) {
            arsort($skipReasons);
            $reasonParts = [];
            foreach (array_slice($skipReasons, 0, 5, true) as $reason => $count) {
                $reasonParts[] = "{$count}× {$reason}";
            }
            $parts[] = 'Skip reasons: ' . implode(', ', $reasonParts) . '.';
        }

        if ($failed > 0) {
            $parts[] = "{$failed} row(s) failed due to format errors.";
        }

        return implode(' ', $parts) ?: null;
    }



    /**
     * Auto-detect flexible contract patch headers.
     */
    private function detectContractPatchHeaders(string $filePath): array
    {
        $rows = SimpleExcelReader::create($filePath)->headerOnRow(0)->getRows();
        $firstRow = $rows->first();

        if (!$firstRow) {
            throw new \Exception('Invalid Format: Contract file is empty.');
        }

        $headerMap = [];
        foreach (array_keys($firstRow) as $header) {
            $headerMap[$this->normalizeHeaderKey((string) $header)] = (string) $header;
        }

        $resolve = function (array $candidates) use ($headerMap): ?string {
            foreach ($candidates as $candidate) {
                if (isset($headerMap[$candidate])) {
                    return $headerMap[$candidate];
                }
            }

            return null;
        };

        $mapping = [
            'contract_id' => $resolve([
                'contract_id',
                'contractid',
                'policy_id',
                'policyid',
                'policy_number',
                'policynumber',
                'contract_number',
                'contractnumber',
            ]),
            'agent_name' => $resolve([
                'agent_name',
                'aligned_agent_name',
                'new_agent_name',
                'agent',
            ]),
            'agent_code' => $resolve([
                'agent_code',
                'aligned_agent_code',
                'new_agent_code',
            ]),
            'department' => $resolve([
                'department',
                'department_name',
                'group_team_sales',
                'group_team',
            ]),
            'payee_name' => $resolve([
                'payee_name',
                'payee',
            ]),
            'flag_value' => $resolve([
                'flag_value',
                'flag',
                'queue_flag',
            ]),
        ];

        if (!$mapping['contract_id']) {
            throw new \Exception(
                'Invalid Format: Contract file must include a Contract ID column (e.g. Contract ID or Policy ID).'
            );
        }

        $hasPatchColumns = !empty($mapping['agent_name'])
            || !empty($mapping['agent_code'])
            || !empty($mapping['department'])
            || !empty($mapping['payee_name']);

        if (!$hasPatchColumns) {
            throw new \Exception(
                'Invalid Format: Contract file must include at least one patch column (Agent Name, Agent Code, Department, or Payee Name).'
            );
        }

        return $mapping;
    }


    // ── Enterprise Excel Writers ─────────────────────────────────────────────

    private function initContractPatchExcelWriter(ImportBatch $batch): array
    {
        Storage::disk('local')->makeDirectory('reconciled_outputs');

        $filename = 'Contract_Patch_' . $batch->id . '.xlsx';
        $outputPath = 'reconciled_outputs/' . $filename;
        $fullPath = Storage::disk('local')->path($outputPath);

        $writer = new XlsxWriter();
        $writer->openToFile($fullPath);

        return [$writer, $outputPath];
    }

    /**
     * Write enterprise audit report header.
     * Columns: CONTRACT_ID | STATUS | REASON | OLD_AGENT | NEW_AGENT |
     *          OLD_PAYEE | NEW_PAYEE | OLD_SOURCE | NEW_SOURCE | SOURCE
     */
    private function writeContractPatchHeader(XlsxWriter $writer): void
    {
        $headers = [
            'CONTRACT_ID',
            'STATUS',
            'REASON',
            'OLD_AGENT_NAME',
            'NEW_AGENT_NAME',
            'OLD_PAYEE_NAME',
            'NEW_PAYEE_NAME',
            'OLD_MATCH_SOURCE',
            'NEW_MATCH_SOURCE',
            'SOURCE',
        ];

        $style = (new Style())
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('1e293b')   // Slate-900 — enterprise dark header
            ->setCellAlignment(CellAlignment::LEFT);

        $writer->addRow(Row::fromValues($headers, $style));
    }

    /**
     * Write a single enterprise audit row.
     */
    private function writeContractPatchRow(XlsxWriter $writer, array $data): void
    {
        $status = strtoupper($data['status'] ?? 'UNKNOWN');

        $writer->addRow(Row::fromValues([
            $data['contract_id'] ?? '',
            $status,
            $data['reason'] ?? '',
            $data['old_agent_name'] ?? '',
            $data['new_agent_name'] ?? '',
            $data['old_payee_name'] ?? '',
            $data['new_payee_name'] ?? '',
            $data['old_match_source'] ?? '',
            $data['new_match_source'] ?? '',
            $data['source'] ?? 'Contract Patch',
        ]));
    }




    private function normalizeHeaderKey(string $header): string
    {
        $key = strtolower(trim($header));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? '';

        return trim($key, '_');
    }

    private function extractContractPatchValue(array $row, ?string $column): string
    {
        if (!$column) {
            return '';
        }

        return trim((string) ($row[$column] ?? ''));
    }

    private function normalizeFlagValue(?string $raw): ?string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        $normalized = strtolower($value);
        $normalized = str_replace(['-', '_'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return match ($normalized) {
            'Home Open' => 'Home Open',
            'Home Close' => 'Home Close',
            default => null,
        };
    }



    // ═════════════════════════════════════════════════════════════════════════
    // STAGE A — REFERENCE MAP BUILDERS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Initialize in-memory caches used for lazy locklist lookups.
     */
    private function initLockListLookup(): void
    {
        $this->lockList = [];
        $this->lockListMisses = [];
    }

    /**
     * Normalize a value for robust matching (trim, strip decimals, handle scientific notation).
     */
    private function normalizePatchId(?string $id): string
    {
        if ($id === null)
            return '';
        $id = trim($id);

        // Handle scientific notation (e.g. 1.23E+07)
        if (preg_match('/^[0-9.]+E\+[0-9]+$/i', $id)) {
            $id = (string) (float) $id;
        }

        // Strip any decimal artifact (.0) for integers
        if (str_contains($id, '.')) {
            $parts = explode('.', $id);
            if (isset($parts[1]) && ($parts[1] === '0' || $parts[1] === '00')) {
                $id = $parts[0];
            }
        }

        return $id;
    }

    private function buildPatchLookupKey(string $contractId, ?string $flag): string
    {
        return $this->normalizePatchId($contractId) . '|' . ($flag ?: '*');
    }

    /**
     * Resolve a locklist entry by policy id with memoization.
     *
     * This avoids eager-loading the full locklist table into memory for large datasets.
     */
    private function getLockListEntry(string $policyId): ?array
    {
        if ($policyId === '') {
            return null;
        }

        if (array_key_exists($policyId, $this->lockList)) {
            return $this->lockList[$policyId];
        }

        if (isset($this->lockListMisses[$policyId])) {
            return null;
        }

        $row = DB::table('lock_lists')
            ->select('agent_name', 'department', 'payee_name')
            ->where('policy_id', $policyId)
            ->first();

        if (!$row) {
            $this->lockListMisses[$policyId] = true;
            return null;
        }

        $entry = [
            'agent_name' => $row->agent_name,
            'department' => $row->department,
            'payee_name' => $row->payee_name,
        ];

        $this->lockList[$policyId] = $entry;

        return $entry;
    }

    /**
     * Build Agency Payee lookup: Department/Agent Name -> Payee Name.
     * Key is normalised so legacy and simplified templates can both resolve.
     */
    private function buildPayeeMap(string $filePath): void
    {
        SimpleExcelReader::create($filePath)->headerOnRow(0)->getRows()->each(function (array $row) {
            $dept = $this->normalizeString($row['DEPARTMENT_NAME'] ?? $row['AGENT_NAME'] ?? '');
            if ($dept) {
                $this->payeeMap[$dept] = $row['PAYEE_NAME'] ?? null;
            }
        });
    }

    /**
     * Build IMS lookup maps.
     *
     * RULE: An IMS row is only indexed if it has a non-empty Department Name.
     * A match without a Department is useless — we cannot derive Payee.
     *
     * Allowed matching keys:
     *   - email (normalised)
     *   - phone (digits only)
     *   - "firstname_lastname" (normalised)
     *   - "dob_lastname" (DOB is never used alone)
     */
    private function buildIMSMap(string $filePath): void
    {
        SimpleExcelReader::create($filePath)->headerOnRow(0)->getRows()->each(function (array $row) {
            $departmentValue = $row['DEPARTMENT_NAME'] ?? $row['AGENT_NAME'] ?? '';
            $department = $this->normalizeString($departmentValue);

            // MANDATORY: Skip IMS rows that have no Department Name.
            if (empty($department)) {
                Log::debug('[ETL][IMS] Row skipped — missing Department Name.', [
                    'agent' => $row['AGENT_ID'] ?? 'N/A',
                ]);
                return;
            }

            $email = $this->normalizeString($row['CLIENT_EMAIL'] ?? '');
            $phone = $this->normalizePhone($row['CLIENT_PHONE'] ?? '');
            $fname = $this->normalizeString($row['SUBMITTER_FIRST_NAME'] ?? $row['CLIENT_FIRST_NAME'] ?? '');
            $lname = $this->normalizeString($row['SUBMITTER_LAST_NAME'] ?? $row['CLIENT_LAST_NAME'] ?? '');
            $dob = $this->normalizeDate($row['CLIENT_DOB'] ?? '');
            $agentName = trim((string) ($row['AGENT_NAME'] ?? ''));

            if ($agentName === '') {
                $agentName = trim(($row['AGENT_FIRST_NAME'] ?? '') . ' ' . ($row['AGENT_LAST_NAME'] ?? ''));
            }

            $record = [
                'transaction_id' => $row['TRANSACTION_ID'] ?? $row['IMS_TRANSACTION_ID'] ?? null,
                'agent_id' => $row['AGENT_ID'] ?? $row['AGENT_CODE'] ?? null,
                'agent_name' => $agentName,
                'department' => $departmentValue, // preserve original casing for output
                'source' => 'ims',
            ];

            // Index by Email (highest confidence)
            if ($email) {
                $this->imsByEmail[$email] = $record;
            }

            // Index by Phone (high confidence)
            if ($phone) {
                $this->imsByPhone[$phone] = $record;
            }

            // Index by First + Last Name
            if ($fname && $lname) {
                $this->imsByFirstLast["{$fname}_{$lname}"] = $record;
            }

            // Index by DOB + Last Name — DOB is NEVER indexed alone
            if ($dob && $lname) {
                $this->imsByDobLast["{$dob}_{$lname}"] = $record;
            }
        });
    }

    /**
     * Build Health Sherpa lookup maps.
     *
     * Matching keys:
     *   - email (normalised)
     *   - phone (digits only) + effective_date (with ±30 day grace window)
     *
     * NOTE: Phone alone without effective_date is NOT indexed. This prevents
     * false positives from phone-number collisions across different policy dates.
     */
    private function buildHealthSherpaMap(string $filePath): void
    {
        SimpleExcelReader::create($filePath)->headerOnRow(0)->getRows()->each(function (array $row) {
            $email = $this->normalizeString($row['EMAIL'] ?? '');
            $phone = $this->normalizePhone($row['PHONE'] ?? '');
            $effectiveDate = $this->normalizeDate($row['EFFECTIVE_DATE'] ?? '');

            $record = [
                'transaction_id' => $row['FFM_APP_ID'] ?? $row['IMS_TRANSACTION_ID'] ?? null,
                'agent_name' => $row['AGENT'] ?? $row['AGENT_NAME'] ?? null,
                'effective_date_ts' => $effectiveDate ? strtotime($effectiveDate) : null,
                'source' => 'health_sherpa',
            ];

            if ($email) {
                $this->hsByEmail[$email] = $record;
            }

            // Phone only indexed when we also have an effective_date (enforces the rule)
            if ($phone && $effectiveDate) {
                $this->hsByPhoneDate[$phone][] = $record;
            }
        });
    }

    // ═════════════════════════════════════════════════════════════════════════
    // STAGE B — MAIN CARRIER STREAM & MATCH
    // ═════════════════════════════════════════════════════════════════════════

    private function streamAndMatchCarrier(string $carrierPath, ImportBatch $batch): void
    {
        $total = 0;
        $processed = 0;
        $failed = 0;
        $imsMatched = 0;
        $hsMatched = 0;
        $locklistMatched = 0;
        $chunk = [];
        $lastRealtimeFlushAt = microtime(true);

        [$writer, $outputPath] = $this->initExcelWriter($batch);

        try {
            $headers = null;
            $reader = SimpleExcelReader::create($carrierPath)->headerOnRow(0);

            $reader->getRows()->each(function (array $row) use (&$total, &$processed, &$failed, &$imsMatched, &$hsMatched, &$locklistMatched, &$chunk, &$headers, $writer, $batch, &$lastRealtimeFlushAt) {
                $total++;

                if ($headers === null) {
                    $headers = array_keys($row);
                    $this->writeExcelHeader($writer, $headers);
                }

                try {
                    [$record, $matchedSources, $wasOverridden] = $this->resolveRecord($row, $batch);
                    $chunk[] = $record;
                    $processed++;

                    if ($wasOverridden) {
                        $locklistMatched++;
                    }

                    if (in_array('ims', $matchedSources)) {
                        $imsMatched++;
                    }
                    if (in_array('health_sherpa', $matchedSources)) {
                        $hsMatched++;
                    }

                    $this->writeExcelRow($writer, $row, $record);

                } catch (\Exception $e) {
                    $failed++;
                    $this->logRowError($batch, $total, $row, $e->getMessage());
                }

                // Realtime progress checkpoints:
                // - every row for very small files (first 50 rows)
                // - every 20 rows afterwards
                // - or at least every ~1.5 seconds for long rows/slow disks
                $shouldFlushRealtime = $total <= 50
                    || ($total % 20 === 0)
                    || ((microtime(true) - $lastRealtimeFlushAt) >= 1.5);

                if ($shouldFlushRealtime) {
                    $batch->update([
                        'total_records' => $total,
                        'processed_records' => $processed,
                        'failed_records' => $failed,
                        'ims_matched_records' => $imsMatched,
                        'hs_matched_records' => $hsMatched,
                        'locklist_matched_records' => $locklistMatched,
                    ]);
                    $lastRealtimeFlushAt = microtime(true);
                }

                if (count($chunk) >= 250) {
                    ReconciliationQueue::insert($chunk);
                    $chunk = [];
                }
            });

            if (!empty($chunk)) {
                ReconciliationQueue::insert($chunk);
            }

        } catch (\Exception $e) {
            Log::error("[ETL Stream Error] Batch {$batch->id}: " . $e->getMessage());
            throw $e;
        } finally {
            $writer->close();
        }

        $status = match (true) {
            $failed > 0 && $processed === 0 => 'failed',
            $failed > 0 => 'completed_with_errors',
            default => 'completed',
        };

        $batch->update([
            'total_records' => $total,
            'processed_records' => $processed,
            'failed_records' => $failed,
            'ims_matched_records' => $imsMatched,
            'hs_matched_records' => $hsMatched,
            'locklist_matched_records' => $locklistMatched,
            'output_file_path' => $outputPath,
            'status' => $status,
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // STAGE C — RECORD RESOLUTION ENGINE (Merge + Locklist)
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Resolve a single BOB/Carrier row against all three stages.
     *
     * Returns [record_array, resolved_source_string, was_overridden_by_locklist].
     */
    private function resolveRecord(array $row, ImportBatch $batch): array
    {
        // ── Extract & normalise BOB identifiers ───────────────────────────────
        $contractId = $row['CONTRACT_ID'] ?? $row['Contract ID'] ?? $row['Policy ID'] ?? null;
        $contractId = trim((string) $contractId);

        if ($contractId === '') {
            throw new \Exception('Missing Contract ID / Policy ID in BOB row.');
        }

        $email = $this->normalizeString($row['MEMBER_EMAIL_ADDRESS'] ?? $row['Member Email'] ?? $row['EMAIL'] ?? '');
        $phone = $this->normalizePhone($row['MEMBER_PHONE_NUMBER'] ?? $row['Member Phone'] ?? $row['PHONE'] ?? '');
        $fname = $this->normalizeString($row['MEMBER_FIRST_NAME'] ?? $row['Member First Name'] ?? '');
        $lname = $this->normalizeString($row['MEMBER_LAST_NAME'] ?? $row['Member Last Name'] ?? '');
        $dobRaw = $this->normalizeDate($row['MEMBER_DOB'] ?? $row['Member DOB'] ?? '');
        $effectiveRaw = $this->normalizeDate($row['COVERAGE_EFFECTIVE_DATE'] ?? $row['Effective Date'] ?? $row['EFFECTIVE_DATE'] ?? '');

        // ── STAGE 1: IMS Matching (runs independently) ───────────────────────
        $imsResult = $this->runIMSFlow($email, $phone, $fname, $lname, $dobRaw);

        // ── STAGE 2: Health Sherpa Matching (runs independently) ─────────────
        $hsResult = $this->runHSFlow($email, $phone, $effectiveRaw);

        // ── PARALLEL MERGE: COMBINE IMS & HS ─────────────────────────────────
        $status = 'pending';
        $matchMethods = [];
        $matchedSources = [];

        $transactionId = null;
        $agentId = null;
        $agentName = null;
        $department = null;
        $payeeName = null;

        // 1. Process IMS Result First (Primary data source)
        if ($imsResult !== null) {
            $matchMethods[] = $imsResult['method'];
            $matchedSources[] = 'ims';
            $status = 'matched';

            $matchedRecord = $imsResult['record'];
            $transactionId = $matchedRecord['transaction_id'] ?? null;
            $agentId = $matchedRecord['agent_id'] ?? null;
            $agentName = $matchedRecord['agent_name'] ?? null;

            // IMS flow: derive Department, then Payee from Payee Map
            $department = $matchedRecord['department'] ?? null;
            if ($department) {
                $normDept = $this->normalizeString((string) $department);
                $payeeName = $this->payeeMap[$normDept] ?? null;
            }
        }

        // 2. Process Health Sherpa Result (Merge supplemental data and method)
        if ($hsResult !== null) {
            $matchMethods[] = $hsResult['method'];
            $matchedSources[] = 'health_sherpa';
            $status = 'matched';

            // Health Sherpa provides transaction_id and agent_name.
            // If IMS was missing this data, we fall back to Health Sherpa's data.
            $transactionId = $transactionId ?: ($hsResult['record']['transaction_id'] ?? null);
            $agentName = $agentName ?: ($hsResult['record']['agent_name'] ?? null);

            // Note: Health Sherpa flow does NOT populate department or payee.
        }

        $matchMethod = empty($matchMethods) ? null : implode(' + ', $matchMethods);

        // ── Capture Original Match Logic (Pre-Override) ────────────────────────
        $originalAgentName = $agentName;
        $originalMatchMethod = $matchMethod;

        // ── STAGE 3: LOCKLIST OVERRIDE (Final Authority) ─────────────────────
        // This runs regardless of match result and overrides everything.
        $wasOverridden = false;
        $lock = $this->getLockListEntry($contractId);

        if ($lock !== null) {
            $agentName = $lock['agent_name'] ?? $agentName;
            $department = $lock['department'] ?? $department;
            $payeeName = $lock['payee_name'] ?? $payeeName;
            $matchMethod = 'LockList Override';
            $status = 'resolved';

            // tag source if previously unmatched, otherwise append locklist
            if (empty($matchedSources)) {
                $matchedSources[] = 'locklist';
            }
            $wasOverridden = true;

            Log::info("[ETL][Locklist] Contract {$contractId} overridden by Locklist.", [
                'batch' => $batch->id,
                'agent_name' => $agentName,
                'department' => $department,
                'payee' => $payeeName,
            ]);
        }

        // ── Log unmatched records ─────────────────────────────────────────────
        if ($status === 'pending') {
            Log::warning("[ETL][Unmatched] Contract {$contractId} not resolved by IMS, HS, or Locklist.", [
                'batch' => $batch->id,
                'email' => $email ?: 'N/A',
                'phone' => $phone ?: 'N/A',
                'name' => "{$fname} {$lname}",
            ]);
        }

        // ── Build final record payload ─────────────────────────────────────────
        $rawEmail = $row['MEMBER_EMAIL_ADDRESS'] ?? $row['Member Email'] ?? $row['EMAIL'] ?? null;

        $record = [
            'id' => (string) Str::ulid(),
            'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
            'import_batch_id' => $batch->id,
            'carrier' => $row['Carrier'] ?? null,
            'contract_id' => $contractId,
            'product' => $row['PRODUCT'] ?? $row['Product'] ?? null,
            'member_first_name' => $row['MEMBER_FIRST_NAME'] ?? $row['Member First Name'] ?? null,
            'member_last_name' => $row['MEMBER_LAST_NAME'] ?? $row['Member Last Name'] ?? null,
            'member_dob' => $dobRaw ? encrypt($dobRaw) : null,
            'member_email' => $rawEmail ? encrypt($rawEmail) : null,
            'member_phone' => $phone ? encrypt($phone) : null,
            'effective_date' => $effectiveRaw ?: null,
            'ims_transaction_id' => $transactionId,
            'agent_id' => $agentId,
            'agent_first_name' => trim($agentName ?? ''),
            'match_method' => $matchMethod,
            'match_confidence' => ($status === 'matched' || $status === 'resolved') ? 100.00 : null,
            'status' => $status,
            'override_flag' => $wasOverridden,
            'override_source' => $wasOverridden ? 'lock_list' : null,
            'original_agent_name' => trim($originalAgentName ?? ''),
            'original_match_method' => $originalMatchMethod,
            'aligned_agent_code' => $agentId,
            'aligned_agent_name' => trim($agentName ?? ''),
            'group_team_sales' => trim($department ?? ''),
            'payee_name' => trim($payeeName ?? ''),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return [$record, $matchedSources, $wasOverridden];
    }

    // ═════════════════════════════════════════════════════════════════════════
    // STAGE 1 — IMS MATCHING FLOW
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Attempt to match a BOB row against the IMS dataset.
     *
     * Matching conditions (evaluated in priority order):
     *   1. Email (exact, normalised)
     *   2. Phone (digits-only exact)
     *   3. FirstName + LastName (normalised concatenation)
     *   4. DOB + LastName (DOB is NEVER matched alone)
     *
     * Returns ['record' => [...], 'method' => 'Email|Phone|...'] or null.
     */
    private function runIMSFlow(
        string $email,
        string $phone,
        string $fname,
        string $lname,
        string $dob
    ): ?array {
        // 1. Email — highest specificity
        if ($email && isset($this->imsByEmail[$email])) {
            return ['record' => $this->imsByEmail[$email], 'method' => 'IMS:Email'];
        }

        // 2. Phone — high specificity
        if ($phone && isset($this->imsByPhone[$phone])) {
            return ['record' => $this->imsByPhone[$phone], 'method' => 'IMS:Phone'];
        }

        // 3. First Name + Last Name (both required)
        if ($fname && $lname && isset($this->imsByFirstLast["{$fname}_{$lname}"])) {
            return ['record' => $this->imsByFirstLast["{$fname}_{$lname}"], 'method' => 'IMS:FirstLastName'];
        }

        // 4. DOB + Last Name (DOB alone is PROHIBITED — always requires Last Name)
        if ($dob && $lname && isset($this->imsByDobLast["{$dob}_{$lname}"])) {
            return ['record' => $this->imsByDobLast["{$dob}_{$lname}"], 'method' => 'IMS:DOB+LastName'];
        }

        return null; // No IMS match found
    }

    // ═════════════════════════════════════════════════════════════════════════
    // STAGE 2 — HEALTH SHERPA MATCHING FLOW
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Attempt to match a BOB row against the Health Sherpa dataset.
     *
     * Matching conditions:
     *   1. Email (exact, normalised)
     *   2. Phone + Effective Date (within ±30 days) — phone alone is NOT enough
     *
     * Returns ['record' => [...], 'method' => 'HS:Email|HS:Phone+Date'] or null.
     *
     * NOTE: This flow only supplies agent_name and transaction_id.
     *       It does NOT handle payee or department.
     */
    private function runHSFlow(string $email, string $phone, string $effectiveDate): ?array
    {
        // 1. Email — highest specificity
        if ($email && isset($this->hsByEmail[$email])) {
            return ['record' => $this->hsByEmail[$email], 'method' => 'HS:Email'];
        }

        // 2. Phone + Effective Date (both are required — enforced by hsByPhoneDate indexing)
        $effectiveTs = $effectiveDate ? strtotime($effectiveDate) : null;

        if ($phone && $effectiveTs && isset($this->hsByPhoneDate[$phone])) {
            foreach ($this->hsByPhoneDate[$phone] as $hsRecord) {
                if ($hsRecord['effective_date_ts']) {
                    $diffDays = abs($effectiveTs - $hsRecord['effective_date_ts']) / 86400;
                    if ($diffDays <= 30) {
                        return ['record' => $hsRecord, 'method' => 'HS:Phone+Date(±30d)'];
                    }
                }
            }
        }

        return null; // No HS match found
    }

    // ═════════════════════════════════════════════════════════════════════════
    // EXCEL OUTPUT HELPERS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Initialize an OpenSpout XLSX writer pointed at a run-specific output file.
     * Returns [writer, relative_storage_path].
     */
    private function initExcelWriter(ImportBatch $batch): array
    {
        Storage::disk('local')->makeDirectory('reconciled_outputs');
        $filename = 'Final_BOB_' . $batch->id . '.xlsx';
        $outputPath = 'reconciled_outputs/' . $filename;
        $fullPath = Storage::disk('local')->path($outputPath);

        $writer = new XlsxWriter();
        $writer->openToFile($fullPath);

        return [$writer, $outputPath];
    }

    /**
     * Write the header row with a styled dark header style.
     */
    private function writeExcelHeader(XlsxWriter $writer, array $bobHeaders): void
    {
        $allHeaders = array_merge($bobHeaders, [
            'Matched Via',
            'IMS Transaction ID',
            'Assigned Agent Code',
            'Assigned Agent Name',
            'Group / Team Sales',
            'Payee Name',
            'Match Status',
            'Override Flag',
            'Override Source',
        ]);

        $style = (new Style())
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('1e293b')
            ->setCellAlignment(CellAlignment::LEFT);

        $writer->addRow(Row::fromValues($allHeaders, $style));
    }

    /**
     * Write a single data row merging original BOB data with reconciliation output.
     */
    private function writeExcelRow(XlsxWriter $writer, array $originalRow, array $record): void
    {
        $cells = array_values($originalRow);

        array_push(
            $cells,
            $record['match_method'] ?? '',
            $record['ims_transaction_id'] ?? '',
            $record['aligned_agent_code'] ?? '',
            $record['aligned_agent_name'] ?? '',
            $record['group_team_sales'] ?? '',
            $record['payee_name'] ?? '',
            $record['status'] ?? '',
            ($record['override_flag'] ?? false) ? 'YES' : '',
            $record['override_source'] ?? '',
        );

        $writer->addRow(Row::fromValues($cells));
    }

    // ═════════════════════════════════════════════════════════════════════════
    // UTILITY — STRING NORMALISERS & VALIDATORS
    // ═════════════════════════════════════════════════════════════════════════

    private function normalizeString(mixed $val): string
    {
        return strtolower(trim((string) $val));
    }

    private function normalizePhone(mixed $val): string
    {
        return preg_replace('/[^0-9]/', '', (string) $val);
    }

    private function normalizeDate(mixed $val): string
    {
        if (!$val)
            return '';

        if ($val instanceof \DateTimeInterface) {
            return $val->format('Y-m-d');
        }

        $str = trim((string) $val);
        if ($str === '')
            return '';

        // Handle specific spaced 'dd mm yy' or 'dd mm yyyy' formats from client test files
        if (preg_match('/^\d{2}\s\d{2}\s\d{2}$/', $str)) {
            $date = \DateTime::createFromFormat('d m y', $str);
            if ($date)
                return $date->format('Y-m-d');
        } elseif (preg_match('/^\d{2}\s\d{2}\s\d{4}$/', $str)) {
            $date = \DateTime::createFromFormat('d m Y', $str);
            if ($date)
                return $date->format('Y-m-d');
        }

        $time = strtotime($str);
        return $time ? date('Y-m-d', $time) : '';
    }

    private function validateHeaders(string $filePath, array $requiredHeaders, string $fileLabel): void
    {
        $rows = SimpleExcelReader::create($filePath)->headerOnRow(0)->getRows();
        $firstRow = $rows->first();

        if (!$firstRow) {
            throw new \Exception("File Error: The {$fileLabel} file is empty. Please check the file content and try again.");
        }

        $headers = array_map(fn($h) => strtolower(trim((string) $h)), array_keys($firstRow));
        $missing = [];

        foreach ($requiredHeaders as $req) {
            if (!in_array(strtolower(trim($req)), $headers)) {
                $missing[] = $req;
            }
        }

        if (!empty($missing)) {
            $msg = "Invalid Format: The {$fileLabel} file is missing the following required columns: "
                . implode(', ', $missing)
                . ". Please ensure your Excel file includes these exact headers.";
            throw new \Exception($msg);
        }
    }

    private function logRowError(ImportBatch $batch, int $rowNum, array $rawData, string $msg): void
    {
        ImportRowError::create([
            'import_batch_id' => $batch->id,
            'row_number' => $rowNum,
            'raw_data' => json_encode($rawData),
            'error_type' => 'validation',
            'error_messages' => json_encode(['error' => $msg]),
        ]);
    }
}
