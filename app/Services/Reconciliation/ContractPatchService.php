<?php

namespace App\Services\Reconciliation;

use App\DTOs\Reconciliation\ContractPatchDTO;
use App\Models\ImportBatch;
use App\Jobs\ProcessContractPatchJob;

class ContractPatchService
{
    public function __construct(
        private readonly FileUploadService $fileUploadService
    ) {}

    public function startContractPatch(ContractPatchDTO $dto): ImportBatch
    {
        $batchData = [
            'parent_batch_id' => $dto->parentBatchId,
            'batch_type' => 'contract_patch',
            'sync_strategy' => 'combined',
            'status' => 'pending',
            'uploaded_by' => $dto->uploadedBy,
        ];

        $batchData['contract_file_path'] = $this->fileUploadService->storeFile($dto->contractFile, 'contract');
        $batchData['contract_original_name'] = $this->fileUploadService->sanitizeFilename($dto->contractFile->getClientOriginalName());

        $batch = ImportBatch::create($batchData);

        ProcessContractPatchJob::dispatch($batch);

        return $batch;
    }

    public function deletePatch(ImportBatch $batch, int $userId): void
    {
        \App\Models\ReconciliationAuditLog::create([
            'transaction_id' => str_pad($batch->id, 32, '0', STR_PAD_RIGHT),
            'modified_by_user_id' => $userId,
            'action' => 'patch_deleted',
            'previous_values' => [],
            'new_values' => ['batch_type' => $batch->batch_type, 'patched_records' => $batch->contract_patched_records],
            'notes' => "User deleted contract patch via individual delete.",
        ]);

        $this->fileUploadService->cleanupBatchAssets($batch);

        $batch->delete();
    }
}
