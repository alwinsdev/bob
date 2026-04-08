<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\ContractPatchLog;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ContractPatchReportingController extends Controller
{
    public function index(Request $request)
    {
        $parentBatches = $this->parentBatchOptions();
        $selectedParentBatchId = $this->resolveParentBatchId($request, $parentBatches->pluck('id')->all());

        $patchBatches = $this->patchBatchOptions($selectedParentBatchId);
        $selectedPatchBatchId = $this->resolvePatchBatchId($request, $patchBatches->pluck('id')->all());

        return view('reconciliation.reporting.contract-patches', [
            'parentBatchOptions' => $this->serializeParentBatches($parentBatches),
            'patchBatchOptions' => $this->serializePatchBatches($patchBatches),
            'selectedParentBatchId' => $selectedParentBatchId,
            'selectedPatchBatchId' => $selectedPatchBatchId,
            'initialContractSearch' => trim((string) $request->input('contract_id', '')),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $request->validate([
            'parent_batch_id' => ['nullable', 'string', 'max:26'],
            'batch_id' => ['nullable', 'string', 'max:26'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $parentBatches = $this->parentBatchOptions();
        $selectedParentBatchId = $this->resolveParentBatchId($request, $parentBatches->pluck('id')->all());

        $patchBatches = $this->patchBatchOptions($selectedParentBatchId);
        $selectedPatchBatchId = $this->resolvePatchBatchId($request, $patchBatches->pluck('id')->all());

        $search = trim((string) $request->input('search', ''));

        $query = ContractPatchLog::query()
            ->with('operator:id,name');

        if ($selectedParentBatchId) {
            $query->where('parent_batch_id', $selectedParentBatchId);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($selectedPatchBatchId) {
            $query->where('batch_id', $selectedPatchBatchId);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('contract_id', 'like', "%{$search}%")
                    ->orWhere('old_agent_name', 'like', "%{$search}%")
                    ->orWhere('new_agent_name', 'like', "%{$search}%")
                    ->orWhere('old_payee_name', 'like', "%{$search}%")
                    ->orWhere('new_payee_name', 'like', "%{$search}%")
                    ->orWhere('old_department', 'like', "%{$search}%")
                    ->orWhere('new_department', 'like', "%{$search}%")
                    ->orWhereHas('operator', function ($operatorQuery) use ($search) {
                        $operatorQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $total = (clone $query)->count();

        $rows = $query
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ContractPatchLog $log) {
                return [
                    'id' => $log->id,
                    'contract_id' => $log->contract_id,
                    'old_agent_name' => $log->old_agent_name,
                    'new_agent_name' => $log->new_agent_name,
                    'old_department' => $log->old_department,
                    'new_department' => $log->new_department,
                    'old_payee_name' => $log->old_payee_name,
                    'new_payee_name' => $log->new_payee_name,
                    'old_match_source' => $log->old_match_source,
                    'new_match_source' => $log->new_match_source,
                    'flag_value' => $log->flag_value,
                    'change_type' => $log->change_type,
                    'updated_by_name' => $log->operator?->name ?? 'System',
                    'queue_record_id' => $log->queue_record_id,
                    'patched_at' => optional($log->created_at)->format('Y-m-d H:i:s'),
                ];
            })
            ->values();

        return response()->json([
            'rows' => $rows,
            'totalCount' => $total,
            'runSummary' => $this->runSummary($selectedPatchBatchId),
        ]);
    }

    private function parentBatchOptions(): Collection
    {
        $query = ImportBatch::query()
            ->topLevel()
            ->where('batch_type', 'standard')
            ->whereIn('status', ['completed', 'completed_with_errors'])
            ->whereHas('childPatches', function ($childQuery) {
                $childQuery->where('batch_type', 'contract_patch');
            })
            ->latest('created_at');

        $parents = $query->limit(40)->get([
            'id',
            'carrier_original_name',
            'created_at',
            'status',
        ]);

        if ($parents->isNotEmpty()) {
            return $parents;
        }

        return ImportBatch::query()
            ->topLevel()
            ->where('batch_type', 'standard')
            ->whereIn('status', ['completed', 'completed_with_errors'])
            ->latest('created_at')
            ->limit(40)
            ->get([
                'id',
                'carrier_original_name',
                'created_at',
                'status',
            ]);
    }

    private function patchBatchOptions(?string $parentBatchId): Collection
    {
        if (!$parentBatchId) {
            return collect();
        }

        return ImportBatch::query()
            ->where('batch_type', 'contract_patch')
            ->where('parent_batch_id', $parentBatchId)
            ->latest('created_at')
            ->get([
                'id',
                'contract_original_name',
                'created_at',
                'status',
                'contract_patched_records',
                'skipped_records',
                'failed_records',
                'processed_records',
                'total_records',
            ]);
    }

    private function resolveParentBatchId(Request $request, array $allowedIds): ?string
    {
        $requestedParentId = trim((string) $request->input('parent_batch_id', ''));

        if ($requestedParentId !== '' && in_array($requestedParentId, $allowedIds, true)) {
            return $requestedParentId;
        }

        return $allowedIds[0] ?? null;
    }

    private function resolvePatchBatchId(Request $request, array $allowedPatchIds): ?string
    {
        $requestedPatchId = trim((string) $request->input('batch_id', ''));

        if ($requestedPatchId !== '' && in_array($requestedPatchId, $allowedPatchIds, true)) {
            return $requestedPatchId;
        }

        return $allowedPatchIds[0] ?? null;
    }

    private function serializeParentBatches(Collection $batches): array
    {
        return $batches->map(function (ImportBatch $batch) {
            $batchDate = optional($batch->created_at)->format('M d, Y');
            $label = trim((string) $batch->carrier_original_name);
            $label = $label !== '' ? $label : 'Standard Batch';

            return [
                'id' => $batch->id,
                'label' => "{$batchDate} | {$label}",
            ];
        })->values()->all();
    }

    private function serializePatchBatches(Collection $batches): array
    {
        return $batches->map(function (ImportBatch $batch) {
            $batchDate = optional($batch->created_at)->format('M d, H:i');
            $label = trim((string) $batch->contract_original_name);
            $label = $label !== '' ? $label : 'Contract Patch';

            return [
                'id' => $batch->id,
                'label' => "{$batchDate} | {$label}",
                'status' => $batch->status,
                'patched' => (int) $batch->contract_patched_records,
                'skipped' => (int) $batch->skipped_records,
                'failed' => (int) $batch->failed_records,
            ];
        })->values()->all();
    }

    private function runSummary(?string $patchBatchId): ?array
    {
        if (!$patchBatchId) {
            return null;
        }

        $batch = ImportBatch::query()
            ->where('batch_type', 'contract_patch')
            ->whereKey($patchBatchId)
            ->first();

        if (!$batch) {
            return null;
        }

        return [
            'id' => $batch->id,
            'contract_original_name' => $batch->contract_original_name,
            'status' => $batch->status,
            'processed_records' => (int) $batch->processed_records,
            'contract_patched_records' => (int) $batch->contract_patched_records,
            'skipped_records' => (int) $batch->skipped_records,
            'failed_records' => (int) $batch->failed_records,
            'skipped_summary' => $batch->skipped_summary ?? [],
            'failure_summary' => $batch->failure_summary ?? [],
            'formatted_date' => optional($batch->created_at)->format('M d, Y h:i A'),
        ];
    }
}
