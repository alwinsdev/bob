<?php

namespace App\Services\Reconciliation;

use App\Models\ImportBatch;
use Illuminate\Contracts\Auth\Access\Authorizable;

class BatchSerializer
{
    public const STATUS_STYLES = [
        'completed' => ['class' => 'bob-badge-matched', 'label' => 'Completed'],
        'processing' => ['class' => 'bob-badge-pending', 'label' => 'Processing'],
        'failed' => ['class' => 'bob-badge-flagged', 'label' => 'Failed'],
        'completed_with_errors' => ['class' => 'bob-badge-flagged', 'label' => 'Partial'],
        'pending' => ['class' => 'bob-badge-pending', 'label' => 'Pending'],
    ];

    public function serialize(ImportBatch $batch, bool $includeChildPatches = true, ?Authorizable $user = null): array
    {
        $isDone = in_array($batch->status, ['completed', 'completed_with_errors'], true);
        $statusInfo = self::STATUS_STYLES[$batch->status] ?? self::STATUS_STYLES['pending'];
        $now = now();

        $processedRecords = (int) $batch->processed_records;
        $totalRecords = (int) $batch->total_records;

        $elapsedSeconds = max(1, $batch->created_at ? $batch->created_at->diffInSeconds($now) : 1);
        $ratePerMin = round(($processedRecords / $elapsedSeconds) * 60, 2);

        $remaining = max(0, $totalRecords - $processedRecords);
        $etaSeconds = null;

        if ($isDone) {
            $etaSeconds = 0;
        } elseif ($ratePerMin > 0) {
            $etaSeconds = (int) round(($remaining / $ratePerMin) * 60);
        }

        $isContractPatch = $batch->isContractPatch();
        $canDownload = (bool) ($user?->can('reconciliation.export.download'));
        $canViewResults = (bool) ($user?->can('reconciliation.results.view'));
        $canViewPatchLedger = $canViewResults;

        $downloadUrl = null;
        if ($batch->hasOutput() && $canDownload) {
            $downloadUrl = $isContractPatch
                ? route('reconciliation.contract-patch.download', $batch)
                : route('reconciliation.batches.download', $batch);
        }

        $childPatches = [];
        if ($includeChildPatches && !$isContractPatch && $batch->relationLoaded('childPatches')) {
            foreach ($batch->childPatches as $child) {
                $childPatches[] = $this->serializeChild($child, $user, $canViewPatchLedger, $canDownload);
            }
        }

        return [
            'id' => $batch->id,
            'batch_type' => $batch->batch_type ?: 'standard',
            'parent_batch_id' => $batch->parent_batch_id,
            'retry_of_batch_id' => $batch->retry_of_batch_id,
            'retry_group_id' => $batch->retry_group_id,
            'attempt_no' => (int) ($batch->attempt_no ?? 1),
            'retry_reason' => $batch->retry_reason,
            'status' => $batch->status,
            'status_label' => $statusInfo['label'],
            'status_class' => $statusInfo['class'],
            'is_done' => $isDone,
            'has_output' => $batch->hasOutput(),
            'download_url' => $downloadUrl,
            'results_url' => $canViewResults && !$isContractPatch && in_array($batch->status, ['completed', 'completed_with_errors'], true)
                ? route('reconciliation.batches.show', $batch)
                : null,
            'processed_records' => $processedRecords,
            'total_records' => $totalRecords,
            'ims_matched_records' => (int) $batch->ims_matched_records,
            'hs_matched_records' => (int) $batch->hs_matched_records,
            'contract_patched_records' => (int) $batch->contract_patched_records,
            'skipped_records' => (int) $batch->skipped_records,
            'failed_records' => (int) $batch->failed_records,
            'skipped_summary' => $batch->skipped_summary ?? [],
            'failure_summary' => $batch->failure_summary ?? [],
            'progress_pct' => $totalRecords ? round(($processedRecords / $totalRecords) * 100) : 0,
            'ims_pct' => $processedRecords ? round(((int)$batch->ims_matched_records / $processedRecords) * 100) : 0,
            'hs_pct' => $processedRecords ? round(((int)$batch->hs_matched_records / $processedRecords) * 100) : 0,
            'elapsed_seconds' => $elapsedSeconds,
            'processed_rate_per_min' => $ratePerMin,
            'eta_seconds' => $etaSeconds,
            'created_at_iso' => optional($batch->created_at)->toIso8601String(),
            'updated_at_iso' => optional($batch->updated_at)->toIso8601String(),
            'error_message' => $batch->error_message,
            'formatted_date' => $batch->created_at ? $batch->created_at->format('M d, Y · h:i A') : '',
            'uploader_name' => $batch->uploadedBy?->name ?? 'System',
            'carrier_original_name' => $batch->carrier_original_name,
            'ims_file_path' => $batch->ims_file_path,
            'ims_original_name' => $batch->ims_original_name,
            'payee_file_path' => $batch->payee_file_path,
            'payee_original_name' => $batch->payee_original_name,
            'health_sherpa_file_path' => $batch->health_sherpa_file_path,
            'health_sherpa_original_name' => $batch->health_sherpa_original_name,
            'contract_file_path' => $batch->contract_file_path,
            'contract_original_name' => $batch->contract_original_name,
            'child_patches' => $childPatches,
        ];
    }

    public function serializeChild(
        ImportBatch $child,
        ?Authorizable $user = null,
        ?bool $canViewPatchLedger = null,
        ?bool $canDownload = null
    ): array
    {
        $statusInfo = self::STATUS_STYLES[$child->status] ?? self::STATUS_STYLES['pending'];
        $isDone = in_array($child->status, ['completed', 'completed_with_errors'], true);
        $canViewPatchLedger = $canViewPatchLedger ?? (bool) ($user?->can('reconciliation.results.view'));
        $canDownload = $canDownload ?? (bool) ($user?->can('reconciliation.export.download'));
        
        $downloadUrl = null;
        if ($child->hasOutput() && $canDownload) {
            $downloadUrl = route('reconciliation.contract-patch.download', $child);
        }

        return [
            'id' => $child->id,
            'batch_type' => $child->batch_type ?: 'contract_patch',
            'parent_batch_id' => $child->parent_batch_id,
            'status' => $child->status,
            'status_label' => $statusInfo['label'],
            'status_class' => $statusInfo['class'],
            'is_done' => $isDone,
            'has_output' => $child->hasOutput(),
            'download_url' => $downloadUrl,
            'ledger_url' => $canViewPatchLedger
                ? route('reconciliation.reporting.contract-patches', [
                    'parent_batch_id' => $child->parent_batch_id,
                    'batch_id' => $child->id,
                ])
                : null,
            'contract_file_path' => $child->contract_file_path,
            'contract_original_name' => $child->contract_original_name,
            'contract_patched_records' => (int) $child->contract_patched_records,
            'skipped_records' => (int) $child->skipped_records,
            'failed_records' => (int) $child->failed_records,
            'skipped_summary' => $child->skipped_summary ?? [],
            'failure_summary' => $child->failure_summary ?? [],
            'total_records' => (int) $child->total_records,
            'progress_pct' => $child->total_records ? round(($child->processed_records / $child->total_records) * 100) : 0,
            'error_message' => $child->error_message,
            'formatted_date' => $child->created_at ? $child->created_at->format('M d, Y · h:i A') : '',
            'uploader_name' => $child->uploadedBy?->name ?? 'System',
            'created_at_iso' => optional($child->created_at)->toIso8601String(),
            'updated_at_iso' => optional($child->updated_at)->toIso8601String(),
        ];
    }
}
