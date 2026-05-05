<?php

namespace App\Services\Reconciliation;

use App\DTOs\Reconciliation\RetryBatchDTO;
use App\DTOs\Reconciliation\UploadBatchDTO;
use App\Jobs\ProcessContractPatchJob;
use App\Models\ImportBatch;
use App\Models\ContractPatchLog;
use App\Models\ReconciliationQueue;
use App\Jobs\ProcessImportBatchJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReconciliationService
{
    public function __construct(
        private readonly FileUploadService $fileUploadService
    ) {}

    public function startSynchronization(UploadBatchDTO $dto): ImportBatch
    {
        return DB::transaction(function () use ($dto) {
            $batchData = [
                'batch_type' => 'standard',
                'sync_strategy' => 'combined',
                'duplicate_strategy' => $dto->duplicateStrategy,
                'status' => 'pending',
                'uploaded_by' => $dto->uploadedBy,
            ];

            $batchData['carrier_file_path'] = $this->fileUploadService->storeFile($dto->carrierFile, 'carrier');
            $batchData['carrier_original_name'] = $this->fileUploadService->sanitizeFilename($dto->carrierFile->getClientOriginalName());

            if ($dto->imsFile) {
                $batchData['ims_file_path'] = $this->fileUploadService->storeFile($dto->imsFile, 'ims');
                $batchData['ims_original_name'] = $this->fileUploadService->sanitizeFilename($dto->imsFile->getClientOriginalName());
            }

            if ($dto->payeeFile) {
                $batchData['payee_file_path'] = $this->fileUploadService->storeFile($dto->payeeFile, 'payee');
                $batchData['payee_original_name'] = $this->fileUploadService->sanitizeFilename($dto->payeeFile->getClientOriginalName());
            }

            if ($dto->healthSherpaFile) {
                $batchData['health_sherpa_file_path'] = $this->fileUploadService->storeFile($dto->healthSherpaFile, 'hs');
                $batchData['health_sherpa_original_name'] = $this->fileUploadService->sanitizeFilename($dto->healthSherpaFile->getClientOriginalName());
            }

            $batch = ImportBatch::create($batchData);

            // afterCommit() — without this a fast worker can pick up the job
            // before the surrounding transaction commits, see no batch row,
            // and hard-fail with a ModelNotFoundException.
            ProcessImportBatchJob::dispatch($batch)->afterCommit();

            return $batch;
        });
    }

    public function rerunSynchronization(ImportBatch $sourceBatch, RetryBatchDTO $dto): ImportBatch
    {
        $this->ensureStandardRerunnableSource($sourceBatch);

        return DB::transaction(function () use ($sourceBatch, $dto) {
            $retryGroupId = $sourceBatch->retry_group_id ?: $sourceBatch->id;
            $attemptNo = max(1, (int) ($sourceBatch->attempt_no ?? 1)) + 1;

            $batchData = [
                'batch_type' => 'standard',
                'sync_strategy' => $sourceBatch->sync_strategy ?: 'combined',
                'duplicate_strategy' => $dto->duplicateStrategy,
                'status' => 'pending',
                'error_message' => null,
                'uploaded_by' => $dto->uploadedBy,
                'retry_of_batch_id' => $sourceBatch->id,
                'retry_group_id' => $retryGroupId,
                'attempt_no' => $attemptNo,
                'retry_reason' => $dto->rerunReason,
                'output_file_path' => null,
            ];

            if ($dto->carrierFile) {
                $batchData['carrier_file_path'] = $this->fileUploadService->storeFile($dto->carrierFile, 'carrier');
                $batchData['carrier_original_name'] = $this->fileUploadService->sanitizeFilename($dto->carrierFile->getClientOriginalName());
            } elseif (blank($sourceBatch->carrier_file_path)) {
                throw ValidationException::withMessages([
                    'carrier_file' => 'Carrier feed is required to rerun synchronization.',
                ]);
            }

            if ($dto->imsFile) {
                $batchData['ims_file_path'] = $this->fileUploadService->storeFile($dto->imsFile, 'ims');
                $batchData['ims_original_name'] = $this->fileUploadService->sanitizeFilename($dto->imsFile->getClientOriginalName());
            }

            if ($dto->payeeFile) {
                $batchData['payee_file_path'] = $this->fileUploadService->storeFile($dto->payeeFile, 'payee');
                $batchData['payee_original_name'] = $this->fileUploadService->sanitizeFilename($dto->payeeFile->getClientOriginalName());
            }

            if ($dto->healthSherpaFile) {
                $batchData['health_sherpa_file_path'] = $this->fileUploadService->storeFile($dto->healthSherpaFile, 'hs');
                $batchData['health_sherpa_original_name'] = $this->fileUploadService->sanitizeFilename($dto->healthSherpaFile->getClientOriginalName());
            }

            $hasIms = $dto->imsFile || !blank($sourceBatch->ims_file_path);
            $hasHs = $dto->healthSherpaFile || !blank($sourceBatch->health_sherpa_file_path);

            if (!$hasIms && !$hasHs) {
                throw ValidationException::withMessages([
                    'source_file' => 'At least one source feed is required for rerun — IMS, Health Sherpa, or both.',
                ]);
            }

            if (!blank($sourceBatch->output_file_path) && Storage::disk('local')->exists($sourceBatch->output_file_path)) {
                Storage::disk('local')->delete($sourceBatch->output_file_path);
            }

            $sourceBatch->fill($batchData);
            $sourceBatch->save();

            ProcessImportBatchJob::dispatch($sourceBatch->fresh())->afterCommit();

            \App\Models\ReconciliationAuditLog::create([
                'transaction_id' => str_pad($sourceBatch->id, 32, '0', STR_PAD_RIGHT),
                'modified_by_user_id' => $dto->uploadedBy,
                'action' => 'batch_rerun_in_place_started',
                'previous_values' => ['source_batch_id' => $sourceBatch->id, 'in_place' => true],
                'new_values' => [
                    'retry_group_id' => $retryGroupId,
                    'attempt_no' => $attemptNo,
                    'duplicate_strategy' => $dto->duplicateStrategy,
                ],
                'notes' => $dto->rerunReason
                    ? "In-place rerun started for {$sourceBatch->id}. Reason: {$dto->rerunReason}"
                    : "In-place rerun started for {$sourceBatch->id}.",
            ]);

            return $sourceBatch->fresh(['uploadedBy']);
        });
    }

    public function rerunContractPatch(
        ImportBatch $sourceBatch,
        ?UploadedFile $contractFile,
        int $uploadedBy,
        ?string $rerunReason = null
    ): ImportBatch {
        $this->ensureContractPatchRerunnableSource($sourceBatch);

        return DB::transaction(function () use ($sourceBatch, $contractFile, $uploadedBy, $rerunReason) {
            $retryGroupId = $sourceBatch->retry_group_id ?: $sourceBatch->id;
            $attemptNo = max(1, (int) ($sourceBatch->attempt_no ?? 1)) + 1;

            $batchData = [
                'status' => 'pending',
                'error_message' => null,
                'uploaded_by' => $uploadedBy,
                'retry_of_batch_id' => $sourceBatch->id,
                'retry_group_id' => $retryGroupId,
                'attempt_no' => $attemptNo,
                'retry_reason' => $rerunReason,
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
            ];

            if ($contractFile) {
                $batchData['contract_file_path'] = $this->fileUploadService->storeFile($contractFile, 'contract');
                $batchData['contract_original_name'] = $this->fileUploadService->sanitizeFilename($contractFile->getClientOriginalName());
            } elseif (blank($sourceBatch->contract_file_path)) {
                throw ValidationException::withMessages([
                    'contract_file' => 'Contract patch file is required to rerun this patch.',
                ]);
            }

            if (!blank($sourceBatch->output_file_path) && Storage::disk('local')->exists($sourceBatch->output_file_path)) {
                Storage::disk('local')->delete($sourceBatch->output_file_path);
            }

            // Roll back prior patch mutations so rerun output fully replaces previous patch results.
            $latestPatchLogs = ContractPatchLog::query()
                ->where('batch_id', $sourceBatch->id)
                ->where('change_type', 'patch_applied')
                ->whereNotNull('queue_record_id')
                ->orderByDesc('created_at')
                ->get([
                    'queue_record_id',
                    'old_agent_code',
                    'old_agent_name',
                    'old_department',
                    'old_payee_name',
                    'old_match_source',
                ])
                ->unique('queue_record_id')
                ->values();

            if ($latestPatchLogs->isNotEmpty()) {
                $now = now();
                foreach ($latestPatchLogs as $patchLog) {
                    ReconciliationQueue::query()
                        ->where('id', $patchLog->queue_record_id)
                        ->where('import_batch_id', $sourceBatch->parent_batch_id)
                        ->update([
                            'match_method' => !blank($patchLog->old_match_source) ? $patchLog->old_match_source : null,
                            'aligned_agent_code' => !blank($patchLog->old_agent_code) ? $patchLog->old_agent_code : null,
                            'aligned_agent_name' => !blank($patchLog->old_agent_name) ? $patchLog->old_agent_name : null,
                            'group_team_sales' => !blank($patchLog->old_department) ? $patchLog->old_department : null,
                            'payee_name' => !blank($patchLog->old_payee_name) ? $patchLog->old_payee_name : null,
                            'is_patched' => false,
                            'resolved_by' => null,
                            'resolved_at' => null,
                            'updated_at' => $now,
                        ]);
                }
            }

            ContractPatchLog::query()
                ->where('batch_id', $sourceBatch->id)
                ->delete();

            $sourceBatch->rowErrors()->delete();

            $sourceBatch->fill($batchData);
            $sourceBatch->save();

            ProcessContractPatchJob::dispatch($sourceBatch->fresh())->afterCommit();

            \App\Models\ReconciliationAuditLog::create([
                'transaction_id' => str_pad($sourceBatch->id, 32, '0', STR_PAD_RIGHT),
                'modified_by_user_id' => $uploadedBy,
                'action' => 'contract_patch_rerun_in_place_started',
                'previous_values' => ['source_batch_id' => $sourceBatch->id, 'in_place' => true],
                'new_values' => [
                    'retry_group_id' => $retryGroupId,
                    'attempt_no' => $attemptNo,
                    'parent_batch_id' => $sourceBatch->parent_batch_id,
                ],
                'notes' => $rerunReason
                    ? "In-place contract patch rerun started for {$sourceBatch->id}. Reason: {$rerunReason}"
                    : "In-place contract patch rerun started for {$sourceBatch->id}.",
            ]);

            return $sourceBatch->fresh(['uploadedBy']);
        });
    }

    private function ensureStandardRerunnableSource(ImportBatch $sourceBatch): void
    {
        if ($sourceBatch->batch_type !== 'standard' || $sourceBatch->parent_batch_id !== null) {
            throw ValidationException::withMessages([
                'batch' => 'Only top-level standard synchronization runs are eligible for rerun.',
            ]);
        }

        if (in_array($sourceBatch->status, ['pending', 'processing'], true)) {
            throw ValidationException::withMessages([
                'batch' => 'This run is still in progress and cannot be rerun yet.',
            ]);
        }
    }

    private function ensureContractPatchRerunnableSource(ImportBatch $sourceBatch): void
    {
        if ($sourceBatch->batch_type !== 'contract_patch' || $sourceBatch->parent_batch_id === null) {
            throw ValidationException::withMessages([
                'batch' => 'Only contract patch runs are eligible for contract rerun.',
            ]);
        }

        if (in_array($sourceBatch->status, ['pending', 'processing'], true)) {
            throw ValidationException::withMessages([
                'batch' => 'This patch run is still in progress and cannot be rerun yet.',
            ]);
        }

        $parentStatus = strtolower((string) optional($sourceBatch->parentBatch)->status);
        if (in_array($parentStatus, ['pending', 'processing'], true)) {
            throw ValidationException::withMessages([
                'batch' => 'The parent standard synchronization is still in progress. Wait for it to finish before rerunning this contract patch.',
            ]);
        }
    }

    public function deleteBatch(ImportBatch $batch, int $userId): void
    {
        DB::transaction(function () use ($batch, $userId) {
            \App\Models\ReconciliationAuditLog::create([
                'transaction_id' => str_pad($batch->id, 32, '0', STR_PAD_RIGHT),
                'modified_by_user_id' => $userId,
                'action' => 'batch_deleted',
                'previous_values' => [],
                'new_values' => ['batch_type' => $batch->batch_type, 'total_records' => $batch->total_records],
                'notes' => "User deleted import batch ({$batch->batch_type}).",
            ]);

            foreach ($batch->childPatches as $child) {
                \App\Models\ReconciliationAuditLog::create([
                    'transaction_id' => str_pad($child->id, 32, '0', STR_PAD_RIGHT),
                    'modified_by_user_id' => $userId,
                    'action' => 'patch_deleted',
                    'previous_values' => [],
                    'new_values' => ['batch_type' => $child->batch_type, 'patched_records' => $child->contract_patched_records],
                    'notes' => "User deleted associated contract patch.",
                ]);
                $this->fileUploadService->cleanupBatchAssets($child);
                $child->rowErrors()->delete();
                // Ensure logs are correctly cascade deleted, if any, via model events or constraints (usually rowErrors are adequate)
                $child->delete();
            }

            $this->fileUploadService->cleanupBatchAssets($batch);
            $batch->rowErrors()->delete();
            $batch->reconciliationRecords()->delete();
            $batch->delete();
        });
    }
}
