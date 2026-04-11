<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\ContractPatchLog;
use App\Models\ImportBatch;
use Illuminate\Database\Eloquent\Builder;
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
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:250'],
            'search' => ['nullable', 'string', 'max:120'],
            'sortModel' => ['nullable', 'string'],
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

        $this->applyReportSort($query, $request->input('sortModel'), [
            'patched_at' => ['created_at'],
            'contract_id' => ['contract_id'],
            'old_agent_name' => ['old_agent_name'],
            'new_agent_name' => ['new_agent_name'],
            'old_department' => ['old_department'],
            'new_department' => ['new_department'],
            'old_payee_name' => ['old_payee_name'],
            'new_payee_name' => ['new_payee_name'],
            'flag_value' => ['flag_value'],
            'change_type' => ['change_type'],
        ], ['created_at'], 'desc');

        $total = (clone $query)->count();
        $perPage = max(1, min((int) $request->integer('limit', 75), 250));
        $page = max(1, (int) $request->integer('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $rows = collect($paginator->items())
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
            'data' => $rows,
            'rows' => $rows,
            'total' => $total,
            'totalCount' => $total,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'runSummary' => $this->runSummary($selectedPatchBatchId, $selectedParentBatchId),
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
        if (!$request->has('batch_id')) {
            return null;
        }

        $requestedPatchId = trim((string) $request->input('batch_id', ''));

        if ($requestedPatchId === '') {
            return null;
        }

        if ($requestedPatchId !== '' && in_array($requestedPatchId, $allowedPatchIds, true)) {
            return $requestedPatchId;
        }

        return null;
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

    private function runSummary(?string $patchBatchId, ?string $parentBatchId = null): ?array
    {
        if (!$patchBatchId && !$parentBatchId) {
            return null;
        }

        if ($patchBatchId) {
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

        $runs = ImportBatch::query()
            ->where('batch_type', 'contract_patch')
            ->where('parent_batch_id', $parentBatchId)
            ->latest('created_at')
            ->get([
                'id',
                'contract_original_name',
                'status',
                'processed_records',
                'contract_patched_records',
                'skipped_records',
                'failed_records',
                'skipped_summary',
                'failure_summary',
                'created_at',
            ]);

        if ($runs->isEmpty()) {
            return null;
        }

        $latestRun = $runs->first();
        $skippedSummary = [];
        $failureSummary = [];

        foreach ($runs as $run) {
            foreach (($run->skipped_summary ?? []) as $reason => $count) {
                $label = trim((string) $reason);
                $label = $label !== '' ? $label : 'Unspecified reason';
                $skippedSummary[$label] = ($skippedSummary[$label] ?? 0) + max(0, (int) $count);
            }

            foreach (($run->failure_summary ?? []) as $reason => $count) {
                $label = trim((string) $reason);
                $label = $label !== '' ? $label : 'Unspecified failure';
                $failureSummary[$label] = ($failureSummary[$label] ?? 0) + max(0, (int) $count);
            }
        }

        arsort($skippedSummary);
        arsort($failureSummary);

        return [
            'id' => null,
            'contract_original_name' => 'All adjustment runs',
            'status' => $runs->count() > 1 ? 'aggregated' : $latestRun->status,
            'processed_records' => (int) $runs->sum('processed_records'),
            'contract_patched_records' => (int) $runs->sum('contract_patched_records'),
            'skipped_records' => (int) $runs->sum('skipped_records'),
            'failed_records' => (int) $runs->sum('failed_records'),
            'skipped_summary' => $skippedSummary,
            'failure_summary' => $failureSummary,
            'formatted_date' => optional($latestRun->created_at)->format('M d, Y h:i A'),
        ];
    }

    private function applyReportSort(Builder $query, ?string $sortModelJson, array $allowedSorts, array $defaultColumns, string $defaultDirection = 'asc'): void
    {
        $sortModel = json_decode((string) $sortModelJson, true);
        $appliedSort = false;

        if (is_array($sortModel)) {
            foreach ($sortModel as $sort) {
                $columns = $allowedSorts[$sort['colId'] ?? ''] ?? null;
                if (!$columns) {
                    continue;
                }

                $direction = strtolower((string) ($sort['sort'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

                foreach ((array) $columns as $column) {
                    $query->orderBy($column, $direction);
                }

                $appliedSort = true;
            }
        }

        if ($appliedSort) {
            return;
        }

        foreach ($defaultColumns as $column) {
            $query->orderBy($column, $defaultDirection);
        }
    }
}
