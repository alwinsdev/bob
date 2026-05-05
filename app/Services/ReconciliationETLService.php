<?php

namespace App\Services;

use App\Models\ContractPatchLog;
use App\Models\ImportBatch;
use App\Models\ImportRowError;
use App\Models\ReconciliationQueue;
use App\Services\Reconciliation\ETL\ReconciliationLookupBuilder;
use App\Services\Reconciliation\ETL\ReconciliationLookupState;
use App\Services\Reconciliation\ETL\ReconciliationRecordResolver;
use App\Services\Reconciliation\ETL\ReconciliationValueNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Spatie\SimpleExcel\SimpleExcelReader;

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
    /** Buffered ContractPatchLog rows — flushed via DB::insert in chunks. */
    private const PATCH_LOG_FLUSH_SIZE = 250;

    private ReconciliationLookupState $lookupState;

    /** @var array<int, array<string, mixed>> */
    private array $patchLogBuffer = [];

    public function __construct(
        private readonly ReconciliationLookupBuilder $lookupBuilder,
        private readonly ReconciliationRecordResolver $recordResolver,
        private readonly ReconciliationValueNormalizer $normalizer
    ) {
        $this->lookupState = new ReconciliationLookupState;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // PUBLIC ENTRY POINT
    // ═════════════════════════════════════════════════════════════════════════

    public function processBatch(ImportBatch $batch): void
    {
        $this->lookupState = new ReconciliationLookupState;
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
            // ── Step 0: Preload lock_lists into RAM once (eliminates per-row N+1
            //           DB lookups during carrier streaming — was up to 40K
            //           queries on a 50K-row batch).
            $this->lookupBuilder->preloadLockList($this->lookupState);

            // ── Step A: Build Agency Payee map (required for IMS Payee lookup) ─
            if ($batch->payee_file_path) {
                $this->lookupBuilder->buildPayeeMap(
                    Storage::disk('local')->path($batch->payee_file_path),
                    $this->lookupState
                );
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
                $this->lookupBuilder->buildIMSMap($imsPath, $this->lookupState);
            }

            // ── Step D: Build Health Sherpa lookup maps ────────────────────────
            if ($hasHs) {
                $hsPath = Storage::disk('local')->path($batch->health_sherpa_file_path);
                $this->validateHeaders($hsPath, ['EMAIL', 'PHONE', 'EFFECTIVE_DATE'], 'Health Sherpa');
                $this->lookupBuilder->buildHealthSherpaMap($hsPath, $this->lookupState);
            }

            // ── Step E: Validate Carrier file ─────────────────────────────────
            if (!$batch->carrier_file_path) {
                throw new \Exception('System Error: The core Carrier (BOB) file is missing from this run.');
            }

            $carrierPath = Storage::disk('local')->path($batch->carrier_file_path);
            $this->validateHeaders($carrierPath, ['CONTRACT_ID', 'MEMBER_FIRST_NAME', 'MEMBER_LAST_NAME'], 'Carrier (BOB)');

            // ── Step F: Stream, match, and write output ────────────────────────
            $this->streamAndMatchCarrier($carrierPath, $batch);

        } catch (\Throwable $e) {
            Log::error("[BATCH #{$batch->id}] Reconciliation Engine Failure: " . $e->getMessage());
            throw $e;
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

    // ═════════════════════════════════════════════════════════════════════════
    // PAYEE BACK-FLOW ANALYSIS ENGINE
    //
    // Reverse-priority 5-source cascade — highest priority probed first,
    // lowest priority probed last. Stops on first definitive payee match.
    //
    //   ⓪ Lock List          (Override — bypasses every cascade step)
    //   ① Final BOB          (Source of Truth — ContractID match)
    //   ② Health Sherpa      (Email > Phone+Date > Phone)
    //   ③ Payee Map          (Department / Agent → Payee dictionary)
    //   ④ IMS                (Email > Phone > Name > DOB+LastName)
    //   ⑤ Carrier BOB        (Last-resort identity fallback)
    //
    // O(N) HashMap engine — all source feeds are pre-indexed by normalized
    // identity keys (Email, Phone, Name, DOB) before the streaming pass.
    // Reuses ReconciliationLookupBuilder so analysis and standard ETL share
    // the same indexing logic.
    //
    // Pure read-only analysis — zero writes to reconciliation_queue.
    // Each cascade decision emits:
    //   • One row to the diagnosed XLSX workbook (download)
    //   • One row to contract_patch_logs (Commission Adjustment Details view)
    // ═════════════════════════════════════════════════════════════════════════

    public function processContractPatch(ImportBatch $batch): void
    {
        $this->lookupState = new ReconciliationLookupState;
        $this->initializeAnalysisBatch($batch);

        try {
            $this->validateAnalysisPrerequisites($batch);

            $parentBatch  = ImportBatch::find($batch->parent_batch_id);
            $contractPath = Storage::disk('local')->path($batch->contract_file_path);

            $columnMap = $this->detectPayeeAnalysisHeaders($contractPath);
            $this->streamPayeeBackFlowAnalysis($contractPath, $batch, $parentBatch, $columnMap);

        } catch (\Throwable $e) {
            Log::error("[PayeeAnalysis][Batch {$batch->id}] Processing failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function initializeAnalysisBatch(ImportBatch $batch): void
    {
        // Wipe any prior contract_patch_logs from a previous analysis run on this batch
        // so the Commission Adjustment Details view reflects the latest cascade only.
        ContractPatchLog::where('batch_id', $batch->id)->delete();

        $batch->update([
            'status'                   => 'processing',
            'error_message'            => null,
            'output_file_path'         => null,
            'total_records'            => 0,
            'processed_records'        => 0,
            'failed_records'           => 0,
            'skipped_records'          => 0,
            'contract_patched_records' => 0,
            'skipped_summary'          => null,
            'failure_summary'          => null,
        ]);
    }

    private function validateAnalysisPrerequisites(ImportBatch $batch): void
    {
        if (empty($batch->contract_file_path)) {
            throw new \Exception('System Error: Missing payee contract file is not present for this analysis run.');
        }

        if (!Storage::disk('local')->exists($batch->contract_file_path)) {
            throw new \Exception('System Error: Missing payee contract file was not found on disk.');
        }

        if (empty($batch->parent_batch_id) || !ImportBatch::where('id', $batch->parent_batch_id)->exists()) {
            throw new \Exception('System Error: Analysis run is not linked to a valid Final BOB parent batch.');
        }
    }

    private function streamPayeeBackFlowAnalysis(
        string      $contractPath,
        ImportBatch $batch,
        ImportBatch $parentBatch,
        array       $mapping
    ): void {
        // Stage 1: Index all 5 source feeds into O(1) hashmaps
        $batch->update(['status_label' => 'Indexing 5 source feeds...']);
        $this->buildAnalysisLookupMaps($parentBatch, $batch);

        // Stage 2: Estimate row count from filesize instead of a full extra pass.
        // SimpleExcelReader::count() materializes every row from disk just to
        // produce a number — wasteful for 100K-row files. Estimating from
        // bytes-per-row keeps the ETA reasonable and skips ~15-30s of I/O.
        $totalRows = $this->estimateRowCount($contractPath);

        $batch->update(['total_records' => $totalRows, 'status_label' => 'Cascading rows...']);

        $state = [
            'processed'  => 0,
            'resolved'   => 0,
            'unresolved' => 0,
            'failed'     => 0,
            'tally'      => [],
            'last_flush' => microtime(true),
        ];

        $this->patchLogBuffer = [];
        [$writer, $outputPath] = $this->initPayeeAnalysisExcelWriter($batch);

        try {
            $this->writePayeeAnalysisHeader($writer);

            SimpleExcelReader::create($contractPath)->headerOnRow(0)->getRows()->each(
                function (array $row) use ($batch, $mapping, $writer, &$state, $totalRows) {
                    $state['processed']++;
                    $this->processAnalysisRow($row, $mapping, $writer, $state, $batch);
                    $this->flushAnalysisProgress($batch, $state, $totalRows);
                }
            );

            // Flush remaining buffered patch log rows (last partial chunk).
            $this->flushPatchLogBuffer();

        } catch (\Throwable $e) {
            // On failure, still flush whatever we've buffered so the audit trail
            // captures partial progress before the exception.
            try { $this->flushPatchLogBuffer(); } catch (\Throwable) {}
            Log::error("[PayeeAnalysis][Batch {$batch->id}] Stream failure: " . $e->getMessage());
            throw $e;
        } finally {
            $writer->close();
        }

        // Reconcile total to the actual processed count so the UI doesn't show
        // an estimate after completion.
        if ($state['processed'] !== $totalRows) {
            $batch->total_records = $state['processed'];
        }

        $this->finalizeAnalysisBatch($batch, $state, $outputPath);
    }

    /**
     * Cheap row-count estimate from filesize — avoids a full extra pass
     * over the file just to display a total. Within ±10% accuracy on
     * typical reconciliation CSVs (200-300 bytes/row).
     */
    private function estimateRowCount(string $path): int
    {
        if (!is_file($path)) {
            return 0;
        }
        $bytes = @filesize($path) ?: 0;
        if ($bytes === 0) {
            return 0;
        }
        // 256 bytes/row average for the missing-payee CSV format
        // (Contract ID + identity columns + light metadata).
        return (int) max(1, round($bytes / 256));
    }

    /**
     * Index all 5 parent-batch source feeds into the shared lookup state.
     * Builders run in REVERSE PRIORITY ORDER — Final BOB first (Source of Truth)
     * down to Carrier BOB last (fallback). This mirrors the cascade probe order
     * so the live progress UI tracks the same step labels.
     *
     * Lock list is preloaded first (⓪) so the cascade's lock-override check
     * runs against an in-memory map, not per-row DB hits.
     */
    private function buildAnalysisLookupMaps(ImportBatch $parentBatch, ImportBatch $batch): void
    {
        // ⓪ Lock List — preload once into RAM (Override layer — bypasses cascade)
        $batch->update(['status_label' => 'Indexing ⓪ Lock List...']);
        $this->lookupBuilder->preloadLockList($this->lookupState);

        // ① Final BOB — Source of Truth (resolved reconciliation_queue rows)
        $batch->update(['status_label' => 'Indexing ① Final BOB...']);
        $this->lookupBuilder->buildFinalBobAnalysisMap($parentBatch->id, $this->lookupState);

        // ② Health Sherpa — FFM marketplace (Email / Phone+Date / Phone)
        if (!empty($parentBatch->health_sherpa_file_path)) {
            $batch->update(['status_label' => 'Indexing ② Health Sherpa...']);
            $path = Storage::disk('local')->path($parentBatch->health_sherpa_file_path);
            if (file_exists($path)) {
                $this->lookupBuilder->buildHealthSherpaMap($path, $this->lookupState);
            }
        }

        // ③ Payee Map — Department / Agent → Payee dictionary
        if (!empty($parentBatch->payee_file_path)) {
            $batch->update(['status_label' => 'Indexing ③ Payee Map...']);
            $path = Storage::disk('local')->path($parentBatch->payee_file_path);
            if (file_exists($path)) {
                $this->lookupBuilder->buildPayeeMap($path, $this->lookupState);
            }
        }

        // ④ IMS — Internal heuristic (Email / Phone / Name / DOB+Last)
        if (!empty($parentBatch->ims_file_path)) {
            $batch->update(['status_label' => 'Indexing ④ IMS...']);
            $path = Storage::disk('local')->path($parentBatch->ims_file_path);
            if (file_exists($path)) {
                $this->lookupBuilder->buildIMSMap($path, $this->lookupState);
            }
        }

        // ⑤ Carrier BOB — last-resort identity fallback
        if (!empty($parentBatch->carrier_file_path)) {
            $batch->update(['status_label' => 'Indexing ⑤ Carrier BOB...']);
            $path = Storage::disk('local')->path($parentBatch->carrier_file_path);
            if (file_exists($path)) {
                $this->lookupBuilder->buildCarrierAnalysisMap($path, $this->lookupState);
            }
        }

        $batch->update(['status_label' => 'Maps ready — starting cascade...']);
    }

    private function processAnalysisRow(array $row, array $mapping, XlsxWriter $writer, array &$state, ImportBatch $batch): void
    {
        $contractIdRaw = '';

        try {
            $contractIdRaw = (string) ($row[$mapping['contract_id']] ?? '');
            $contractId    = $this->normalizer->patchId($contractIdRaw);

            if ($contractId === '') {
                $this->handleFailedAnalysisRow($writer, $contractIdRaw, 'Invalid Format: Missing or empty Contract ID.', $state, $batch);
                return;
            }

            $rowIdentity = [
                'contract_id' => $contractId,
                'first_name'  => $this->normalizer->extractColumnValue($row, $mapping['first_name']     ?? null),
                'last_name'   => $this->normalizer->extractColumnValue($row, $mapping['last_name']      ?? null),
                'mobile'      => $this->normalizer->extractColumnValue($row, $mapping['mobile']         ?? null),
                'email'       => $this->normalizer->extractColumnValue($row, $mapping['email']          ?? null),
                'dob'         => $this->normalizer->extractColumnValue($row, $mapping['dob']            ?? null),
                'effective'   => $this->normalizer->extractColumnValue($row, $mapping['effective_date'] ?? null),
            ];

            $result = $this->resolvePayeeBackFlow($contractId, $rowIdentity);

            if (in_array($result['status'], ['RESOLVED', 'LOCK_OVERRIDE'], true)) {
                $state['resolved']++;
                $state['tally'][$result['match_source']] = ($state['tally'][$result['match_source']] ?? 0) + 1;
            } else {
                $state['unresolved']++;
                $state['tally']['Unresolved'] = ($state['tally']['Unresolved'] ?? 0) + 1;
            }

            $this->writePayeeAnalysisRow($writer, array_merge($rowIdentity, $result));
            $this->persistContractPatchLog($batch, $rowIdentity, $result);

        } catch (\Throwable $e) {
            Log::warning("[PayeeAnalysis][Batch {$batch->id}] Row error: " . $e->getMessage());
            $this->handleFailedAnalysisRow($writer, $contractIdRaw, 'System error processing this row.', $state, $batch);
        }
    }

    /**
     * 5-source REVERSE-ORDER cascade resolver.
     *
     * Returns the first definitive payee attribution found, walking sources
     * in priority order and recording WHICH key fired the match for diagnosis.
     */
    private function resolvePayeeBackFlow(string $contractId, array $row): array
    {
        $key       = strtolower($contractId);
        $email     = $this->normalizer->string($row['email']      ?? '');
        $phone     = $this->normalizer->phone($row['mobile']      ?? '');
        $firstName = $this->normalizer->string($row['first_name'] ?? '');
        $lastName  = $this->normalizer->string($row['last_name']  ?? '');
        $dob       = $this->normalizer->date($row['dob']          ?? '');
        $effective = $this->normalizer->date($row['effective']    ?? '');

        // ── ⓪ LOCK LIST OVERRIDE ────────────────────────────────────────────
        $lock = $this->lookupBuilder->getLockListEntry($contractId, $this->lookupState);
        if ($lock !== null && (!empty($lock['payee_name']) || !empty($lock['agent_name']))) {
            return $this->buildResult(
                payee:     (string) ($lock['payee_name'] ?? ''),
                agentName: (string) ($lock['agent_name'] ?? ''),
                agentCode: '',
                dept:      (string) ($lock['department'] ?? ''),
                source:    'Lock List',
                matchKey:  'ContractID',
                status:    'LOCK_OVERRIDE',
                diagnosis: "Lock List Override: Contract {$contractId} is locked. Bypassing cascade — Agent/Payee assignment is absolute."
            );
        }

        // ── ① FINAL BOB (Source of Truth) ───────────────────────────────────
        $finalBob = $this->lookupState->finalBobByContract[$key] ?? null;
        if ($finalBob !== null && $finalBob['payee_name'] !== '') {
            return $this->buildResult(
                payee:     $finalBob['payee_name'],
                agentName: $finalBob['agent_name'],
                agentCode: $finalBob['agent_code'],
                dept:      $finalBob['department'],
                source:    'Final BOB',
                matchKey:  'ContractID',
                status:    'RESOLVED',
                diagnosis: 'Resolved via Final BOB (ContractID). Source of Truth from prior reconciled batch.'
            );
        }

        // ── ② HEALTH SHERPA (FFM marketplace) ──────────────────────────────
        $hsHit       = null;
        $hsMatchKey  = '';
        $hsResolvedDept = '';

        if ($email !== '' && isset($this->lookupState->hsByEmail[$email])) {
            $hsHit = $this->lookupState->hsByEmail[$email];
            $hsMatchKey = 'Email';
        } elseif ($phone !== '' && $effective !== '' && !empty($this->lookupState->hsByPhoneDate[$phone])) {
            $effTs = strtotime($effective) ?: null;
            foreach ($this->lookupState->hsByPhoneDate[$phone] as $candidate) {
                if ($effTs && ($candidate['effective_date_ts'] ?? null) === $effTs) {
                    $hsHit = $candidate;
                    $hsMatchKey = 'Phone+EffectiveDate';
                    break;
                }
            }
            if ($hsHit === null) {
                $hsHit = $this->lookupState->hsByPhoneDate[$phone][0];
                $hsMatchKey = 'Phone (date best-effort)';
            }
        } elseif ($phone !== '' && isset($this->lookupState->hsByPhone[$phone])) {
            $hsHit = $this->lookupState->hsByPhone[$phone];
            $hsMatchKey = 'Phone';
        }

        if ($hsHit !== null) {
            $hsPayee = trim((string) ($hsHit['payee_name'] ?? ''));
            $hsAgent = trim((string) ($hsHit['agent_name'] ?? ''));
            $hsCode  = trim((string) ($hsHit['agent_id']   ?? ''));
            $hsResolvedDept = trim((string) ($hsHit['department'] ?? ''));

            if ($hsPayee !== '') {
                return $this->buildResult(
                    payee:     $hsPayee,
                    agentName: $hsAgent,
                    agentCode: $hsCode,
                    dept:      $hsResolvedDept,
                    source:    'Health Sherpa',
                    matchKey:  $hsMatchKey,
                    status:    'RESOLVED',
                    diagnosis: "Resolved via Health Sherpa ({$hsMatchKey}). FFM marketplace attribution."
                );
            }
        }

        // ── ③ PAYEE MAP (Department / Agent dictionary) ────────────────────
        // Priority order pivots: HS-resolved department → IMS-resolved department.
        if ($hsResolvedDept !== '' && isset($this->lookupState->payeeMap[strtolower($hsResolvedDept)])) {
            return $this->buildResult(
                payee:     (string) $this->lookupState->payeeMap[strtolower($hsResolvedDept)],
                agentName: trim((string) ($hsHit['agent_name'] ?? '')),
                agentCode: trim((string) ($hsHit['agent_id']   ?? '')),
                dept:      $hsResolvedDept,
                source:    'Payee Map',
                matchKey:  "HS → Department",
                status:    'RESOLVED',
                diagnosis: "Resolved via Payee Map (Department: {$hsResolvedDept}). HS surfaced the department; Payee Map dictionary supplied the Payee."
            );
        }

        // ── ④ IMS (internal heuristic) ─────────────────────────────────────
        $imsHit      = null;
        $imsMatchKey = '';

        if ($email !== '' && isset($this->lookupState->imsByEmail[$email])) {
            $imsHit = $this->lookupState->imsByEmail[$email];
            $imsMatchKey = 'Email';
        } elseif ($phone !== '' && isset($this->lookupState->imsByPhone[$phone])) {
            $imsHit = $this->lookupState->imsByPhone[$phone];
            $imsMatchKey = 'Phone';
        } elseif ($firstName !== '' && $lastName !== '' && isset($this->lookupState->imsByFirstLast["{$firstName}_{$lastName}"])) {
            $imsHit = $this->lookupState->imsByFirstLast["{$firstName}_{$lastName}"];
            $imsMatchKey = 'Name';
        } elseif ($dob !== '' && $lastName !== '' && isset($this->lookupState->imsByDobLast["{$dob}_{$lastName}"])) {
            $imsHit = $this->lookupState->imsByDobLast["{$dob}_{$lastName}"];
            $imsMatchKey = 'DOB+LastName';
        }

        if ($imsHit !== null) {
            $imsPayee = trim((string) ($imsHit['payee_name'] ?? ''));
            $imsDept  = trim((string) ($imsHit['department'] ?? ''));
            $imsAgent = trim((string) ($imsHit['agent_name'] ?? ''));
            $imsCode  = trim((string) ($imsHit['agent_id']   ?? ''));

            if ($imsPayee !== '') {
                return $this->buildResult(
                    payee:     $imsPayee,
                    agentName: $imsAgent,
                    agentCode: $imsCode,
                    dept:      $imsDept,
                    source:    'IMS',
                    matchKey:  $imsMatchKey,
                    status:    'RESOLVED',
                    diagnosis: "Resolved via IMS ({$imsMatchKey}). Payee directly attached to IMS transaction."
                );
            }

            // ③ Payee Map late pivot via IMS-resolved Department / Agent
            if ($imsDept !== '' && isset($this->lookupState->payeeMap[strtolower($imsDept)])) {
                return $this->buildResult(
                    payee:     (string) $this->lookupState->payeeMap[strtolower($imsDept)],
                    agentName: $imsAgent,
                    agentCode: $imsCode,
                    dept:      $imsDept,
                    source:    'Payee Map',
                    matchKey:  "IMS:{$imsMatchKey} → Department",
                    status:    'RESOLVED',
                    diagnosis: "Resolved via Payee Map (Department: {$imsDept}). IMS matched on {$imsMatchKey} but lacked Payee — Payee Map dictionary supplied the answer."
                );
            }
            if ($imsAgent !== '' && isset($this->lookupState->payeeMap[strtolower($imsAgent)])) {
                return $this->buildResult(
                    payee:     (string) $this->lookupState->payeeMap[strtolower($imsAgent)],
                    agentName: $imsAgent,
                    agentCode: $imsCode,
                    dept:      $imsDept,
                    source:    'Payee Map',
                    matchKey:  "IMS:{$imsMatchKey} → Agent",
                    status:    'RESOLVED',
                    diagnosis: "Resolved via Payee Map (Agent: {$imsAgent}). IMS matched on {$imsMatchKey} but lacked Payee — Agent-keyed dictionary lookup."
                );
            }
            // IMS hit but no payee + no Payee Map fallback → fall through to Carrier
        }

        // ── ① CARRIER BOB (last-resort identity fallback) ───────────────────
        $carrierHit      = null;
        $carrierMatchKey = '';

        if (isset($this->lookupState->carrierByContract[$key])) {
            $carrierHit = $this->lookupState->carrierByContract[$key];
            $carrierMatchKey = 'ContractID';
        } elseif ($email !== '' && isset($this->lookupState->carrierByEmail[$email])) {
            $carrierHit = $this->lookupState->carrierByEmail[$email];
            $carrierMatchKey = 'Email';
        } elseif ($phone !== '' && isset($this->lookupState->carrierByPhone[$phone])) {
            $carrierHit = $this->lookupState->carrierByPhone[$phone];
            $carrierMatchKey = 'Phone';
        } elseif ($firstName !== '' && $lastName !== '' && isset($this->lookupState->carrierByFirstLast["{$firstName}_{$lastName}"])) {
            $carrierHit = $this->lookupState->carrierByFirstLast["{$firstName}_{$lastName}"];
            $carrierMatchKey = 'Name';
        } elseif ($dob !== '' && $lastName !== '' && isset($this->lookupState->carrierByDobLast["{$dob}_{$lastName}"])) {
            $carrierHit = $this->lookupState->carrierByDobLast["{$dob}_{$lastName}"];
            $carrierMatchKey = 'DOB+LastName';
        }

        if ($carrierHit !== null) {
            $carrierPayee = trim((string) ($carrierHit['payee_name'] ?? ''));
            $carrierAgent = trim((string) ($carrierHit['agent_name'] ?? ''));
            $carrierCode  = trim((string) ($carrierHit['agent_code'] ?? ''));
            $carrierDept  = trim((string) ($carrierHit['department'] ?? ''));

            if ($carrierPayee === '' && $carrierDept !== '' && isset($this->lookupState->payeeMap[strtolower($carrierDept)])) {
                $carrierPayee = (string) $this->lookupState->payeeMap[strtolower($carrierDept)];
            }

            if ($carrierPayee !== '') {
                return $this->buildResult(
                    payee:     $carrierPayee,
                    agentName: $carrierAgent,
                    agentCode: $carrierCode,
                    dept:      $carrierDept,
                    source:    'Carrier BOB',
                    matchKey:  $carrierMatchKey,
                    status:    'RESOLVED',
                    diagnosis: "Resolved via Carrier BOB ({$carrierMatchKey}). Last-resort identity fallback from original carrier feed."
                );
            }

            // Identity confirmed but no payee anywhere → identity mismatch
            return $this->buildResult(
                payee:     '',
                agentName: $carrierAgent,
                agentCode: $carrierCode,
                dept:      $carrierDept,
                source:    'Unresolved',
                matchKey:  "Carrier:{$carrierMatchKey}",
                status:    'UNRESOLVED',
                diagnosis: "Identity confirmed in Carrier BOB ({$carrierMatchKey}) but no Payee resolved across IMS, Health Sherpa, Payee Map, or Final BOB."
            );
        }

        // ── No matches anywhere ─────────────────────────────────────────────
        return $this->buildResult(
            payee:     '',
            agentName: '',
            agentCode: '',
            dept:      '',
            source:    'Unresolved',
            matchKey:  'None',
            status:    'UNRESOLVED',
            diagnosis: 'No match found in all 5 sources (Final BOB, IMS, Health Sherpa, Payee Map, Carrier BOB).'
        );
    }

    /** Standardize cascade output shape for downstream writers + audit log. */
    private function buildResult(
        string $payee,
        string $agentName,
        string $agentCode,
        string $dept,
        string $source,
        string $matchKey,
        string $status,
        string $diagnosis
    ): array {
        return [
            'resolved_payee'      => $payee,
            'resolved_agent'      => $agentName,
            'resolved_agent_code' => $agentCode,
            'resolved_dept'       => $dept,
            'match_source'        => $source,
            'match_key'           => $matchKey,
            'diagnosis'           => $diagnosis,
            'status'              => $status,
        ];
    }

    /**
     * Buffer one Commission Adjustment audit row capturing the cascade decision.
     * Old values come from the Final BOB queue (when present) so the report
     * surfaces a true before/after for every contract row in the analysis run.
     *
     * Rows are flushed in chunks of {@see self::PATCH_LOG_FLUSH_SIZE} via raw
     * DB::insert — this eliminates the per-row INSERT N+1 antipattern (was
     * ~100K round-trips on a 100K-row analysis; now ~400 chunked INSERTs).
     *
     * Audit fields (`change_type`, `updated_by`, `flag_value`) are set here
     * explicitly because they are NOT in $fillable on ContractPatchLog —
     * mass-assignment guard prevents future controllers from spoofing them.
     */
    private function persistContractPatchLog(ImportBatch $batch, array $rowIdentity, array $result): void
    {
        $contractId = (string) ($rowIdentity['contract_id'] ?? '');
        if ($contractId === '') {
            return;
        }
        $finalBob = $this->lookupState->finalBobByContract[strtolower($contractId)] ?? null;

        $changeType = match ($result['status'] ?? 'FAILED') {
            'LOCK_OVERRIDE' => 'analysis_lock_override',
            'RESOLVED'      => 'analysis_resolved',
            'UNRESOLVED'    => 'analysis_unresolved',
            default         => 'analysis_failed',
        };

        $now = now();

        $this->patchLogBuffer[] = [
            'id'                => (string) Str::ulid(),
            'contract_id'       => $contractId,
            'batch_id'          => $batch->id,
            'parent_batch_id'   => $batch->parent_batch_id,
            'previous_batch_id' => null,
            'old_agent_code'    => $finalBob['agent_code'] ?? null,
            'old_agent_name'    => $finalBob['agent_name'] ?? null,
            'old_department'    => $finalBob['department'] ?? null,
            'old_payee_name'    => $finalBob['payee_name'] ?? null,
            'old_match_source'  => $finalBob['match_method'] ?? null,
            'new_agent_code'    => ($result['resolved_agent_code'] ?? '') !== '' ? $result['resolved_agent_code'] : null,
            'new_agent_name'    => ($result['resolved_agent']      ?? '') !== '' ? $result['resolved_agent']      : null,
            'new_department'    => ($result['resolved_dept']       ?? '') !== '' ? $result['resolved_dept']       : null,
            'new_payee_name'    => ($result['resolved_payee']      ?? '') !== '' ? $result['resolved_payee']      : null,
            'new_match_source'  => $result['match_source'] ?? 'Unresolved',
            'match_key'         => $result['match_key']    ?? null,
            'diagnosis'         => $result['diagnosis']    ?? null,
            'flag_value'        => null,
            'change_type'       => $changeType,
            'updated_by'        => $batch->uploaded_by,
            'queue_record_id'   => null,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];

        if (count($this->patchLogBuffer) >= self::PATCH_LOG_FLUSH_SIZE) {
            $this->flushPatchLogBuffer();
        }
    }

    /** Bulk-insert the buffered patch log rows and reset the buffer. */
    private function flushPatchLogBuffer(): void
    {
        if (empty($this->patchLogBuffer)) {
            return;
        }
        // Use the model's table to stay schema-aware, but go through DB::table
        // for a direct bulk insert — Eloquent's create() emits one INSERT per row.
        DB::table((new ContractPatchLog)->getTable())->insert($this->patchLogBuffer);
        $this->patchLogBuffer = [];
    }

    private function handleFailedAnalysisRow(XlsxWriter $writer, string $contractId, string $diagnosis, array &$state, ImportBatch $batch): void
    {
        $state['failed']++;
        $state['tally']['Failed'] = ($state['tally']['Failed'] ?? 0) + 1;

        $row = [
            'contract_id'         => $contractId,
            'first_name'          => '',
            'last_name'           => '',
            'mobile'               => '',
            'email'               => '',
            'dob'                 => '',
            'resolved_payee'      => '',
            'resolved_agent'      => '',
            'resolved_agent_code' => '',
            'resolved_dept'       => '',
            'match_source'        => 'FAILED',
            'match_key'           => '—',
            'diagnosis'           => $diagnosis,
            'status'              => 'FAILED',
        ];

        $this->writePayeeAnalysisRow($writer, $row);

        if ($contractId !== '') {
            $this->persistContractPatchLog($batch, $row, $row);
        }
    }

    private function flushAnalysisProgress(ImportBatch $batch, array &$state, int $totalRows): void
    {
        $processed = $state['processed'];
        if ($processed <= 50 || ($processed % 50 === 0) || (microtime(true) - $state['last_flush']) >= 2.0) {
            $progress = $totalRows > 0 ? min(98, (int) round(($processed / $totalRows) * 100)) : 10;
            $batch->update([
                'processed_records'        => $processed,
                'failed_records'           => $state['failed'],
                'contract_patched_records' => $state['resolved'],
                'skipped_records'          => $state['unresolved'],
                'status_label'             => "Analysing: {$processed}" . ($totalRows > 0 ? "/{$totalRows}" : ''),
                'progress_pct'             => $progress,
            ]);
            $state['last_flush'] = microtime(true);
        }
    }

    private function finalizeAnalysisBatch(ImportBatch $batch, array $state, string $outputPath): void
    {
        $status = match (true) {
            $state['failed'] > 0 && $state['resolved'] === 0 && $state['unresolved'] === 0 => 'failed',
            $state['failed'] > 0 => 'completed_with_errors',
            default              => 'completed',
        };

        arsort($state['tally']);
        $summaryParts = [];
        foreach (array_slice($state['tally'], 0, 5, true) as $src => $cnt) {
            $summaryParts[] = "{$cnt}× {$src}";
        }
        
        $errorSummary = $state['resolved'] > 0
            ? "Resolved {$state['resolved']}/{$state['processed']} rows. Sources: " . implode(', ', $summaryParts) . '.'
            : "No payees resolved across {$state['processed']} rows. " . implode(', ', $summaryParts);

        $batch->update([
            'total_records'            => $state['processed'],
            'processed_records'        => $state['processed'],
            'failed_records'           => $state['failed'],
            'skipped_records'          => $state['unresolved'],
            'contract_patched_records' => $state['resolved'],
            'skipped_summary'          => $state['tally'],
            'failure_summary'          => [],
            'output_file_path'         => $outputPath,
            'status'                   => $status,
            'status_label'             => match ($status) {
                'completed'             => 'Completed',
                'completed_with_errors' => 'Partial',
                default                 => 'Failed',
            },
            'progress_pct'  => 100,
            'error_message' => $status !== 'completed' ? $errorSummary : null,
        ]);

        Log::info("[PayeeAnalysis][Batch {$batch->id}] Complete — Resolved: {$state['resolved']}, Unresolved: {$state['unresolved']}");
    }

    /**
     * Detect required + optional columns in the uploaded missing-payee file.
     * Required: Contract ID, First Name, Last Name.
     * Optional: Mobile, Email, DOB, Effective Date — these unlock additional
     * cascade keys (HS Phone+Date, IMS DOB+LastName, IMS/Carrier Email).
     */
    private function detectPayeeAnalysisHeaders(string $filePath): array
    {
        $firstRow = SimpleExcelReader::create($filePath)->headerOnRow(0)->getRows()->first();

        if (!$firstRow) {
            throw new \Exception('Invalid Format: Missing payee file is empty.');
        }

        $headerMap = [];
        foreach (array_keys($firstRow) as $header) {
            $headerMap[$this->normalizer->headerKey((string) $header)] = (string) $header;
        }

        $resolve = function (array $candidates) use ($headerMap): ?string {
            foreach ($candidates as $c) {
                if (isset($headerMap[$c])) return $headerMap[$c];
            }
            return null;
        };

        $contractId    = $resolve(['contract_id', 'contractid', 'policy_id', 'policyid', 'policy_number', 'policynumber', 'contract_number', 'contractnumber']);
        $firstName     = $resolve(['first_name', 'firstname', 'subscriber_first_name', 'subscriber_firstname', 'subscriberfirstname', 'member_first_name', 'member_firstname', 'client_first_name', 'client_firstname', 'fname']);
        $lastName      = $resolve(['last_name', 'lastname', 'subscriber_last_name', 'subscriber_lastname', 'subscriberlastname', 'member_last_name', 'member_lastname', 'client_last_name', 'client_lastname', 'lname']);
        $mobile        = $resolve(['mobile', 'phone', 'phone_number', 'mobile_number', 'cell', 'contact', 'subscriber_phone', 'member_phone', 'client_phone']);
        $email         = $resolve(['email', 'email_address', 'subscriber_email', 'member_email', 'client_email']);
        $dob           = $resolve(['dob', 'date_of_birth', 'birth_date', 'birthdate', 'subscriber_dob', 'member_dob', 'client_dob']);
        $effectiveDate = $resolve(['effective_date', 'effectivedate', 'eff_date', 'effective', 'policy_effective_date', 'coverage_effective_date']);

        if (!$contractId) {
            throw new \Exception('Invalid Format: Missing payee file must include a Contract ID column (e.g. Contract ID or Policy ID).');
        }
        if (!$firstName || !$lastName) {
            throw new \Exception('Invalid Format: Missing payee file must include Subscriber First Name and Last Name columns.');
        }

        return [
            'contract_id'    => $contractId,
            'first_name'     => $firstName,
            'last_name'      => $lastName,
            'mobile'         => $mobile,
            'email'          => $email,
            'dob'            => $dob,
            'effective_date' => $effectiveDate,
        ];
    }

    // ── Excel Writers — Payee Back-Flow Analysis ─────────────────────────────

    private function initPayeeAnalysisExcelWriter(ImportBatch $batch): array
    {
        Storage::disk('local')->makeDirectory('reconciled_outputs');
        $filename   = 'Payee_Analysis_' . $batch->id . '.xlsx';
        $outputPath = 'reconciled_outputs/' . $filename;
        $fullPath   = Storage::disk('local')->path($outputPath);
        $writer     = new XlsxWriter;
        $writer->openToFile($fullPath);
        return [$writer, $outputPath];
    }

    private function writePayeeAnalysisHeader(XlsxWriter $writer): void
    {
        $style = (new Style)
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('1e293b')
            ->setCellAlignment(CellAlignment::LEFT);

        $writer->addRow(Row::fromValues([
            'CONTRACT_ID', 'FIRST_NAME', 'LAST_NAME', 'MOBILE', 'EMAIL', 'DOB',
            'RESOLVED_PAYEE', 'RESOLVED_AGENT', 'RESOLVED_AGENT_CODE', 'RESOLVED_DEPARTMENT',
            'MATCH_SOURCE', 'MATCH_KEY', 'DIAGNOSIS', 'STATUS',
        ], $style));
    }

    private function writePayeeAnalysisRow(XlsxWriter $writer, array $data): void
    {
        $writer->addRow(Row::fromValues(array_map(
            [$this, 'sanitizeSpreadsheetCell'],
            [
                $data['contract_id']         ?? '',
                $data['first_name']          ?? '',
                $data['last_name']           ?? '',
                $data['mobile']              ?? '',
                $data['email']               ?? '',
                $data['dob']                 ?? '',
                $data['resolved_payee']      ?? '',
                $data['resolved_agent']      ?? '',
                $data['resolved_agent_code'] ?? '',
                $data['resolved_dept']       ?? '',
                $data['match_source']        ?? '',
                $data['match_key']           ?? '',
                $data['diagnosis']           ?? '',
                $data['status']              ?? '',
            ]
        )));
    }

    /**
     * Neutralize Excel/CSV formula triggers (`=`, `+`, `-`, `@`, TAB, CR) by
     * prefixing a literal apostrophe. Without this an uploaded payee file
     * containing `=cmd|'/c calc'!A1` becomes RCE the moment an analyst opens
     * the downloaded workbook.
     */
    private function sanitizeSpreadsheetCell(mixed $value): mixed
    {
        if ($value === null || $value === '' || !is_string($value)) {
            return $value;
        }
        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }
        return $value;
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
                    [$record, $matchedSources, $wasOverridden] = $this->recordResolver->resolve(
                        $row,
                        $batch,
                        $this->lookupState
                    );
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

        $writer = new XlsxWriter;
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

        $style = (new Style)
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
                . '. Please ensure your Excel file includes these exact headers.';
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
