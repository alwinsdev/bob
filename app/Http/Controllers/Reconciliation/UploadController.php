<?php

namespace App\Http\Controllers\Reconciliation;

use App\DTOs\Reconciliation\RetryBatchDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\RerunBatchRequest;
use App\Http\Requests\UploadBatchRequest;
use App\Models\ImportBatch;
use App\Models\ImportRowError;
use App\DTOs\Reconciliation\UploadBatchDTO;
use App\Services\Reconciliation\ReconciliationService;
use App\Services\Reconciliation\BatchSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function __construct(
        private readonly ReconciliationService $reconciliationService,
        private readonly BatchSerializer $batchSerializer
    ) {}

    public function index()
    {
        abort_unless(auth()->user()?->can('create', ImportBatch::class), 403);

        // Only top-level batches — nested contract patches are embedded inside their parent row
        $batches = ImportBatch::with(['uploadedBy', 'childPatches.uploadedBy'])
            ->topLevel()
            ->latest()
            ->paginate(20);

        $allIds = $batches->getCollection()->flatMap(function (ImportBatch $b) {
            return $b->childPatches->pluck('id')->prepend($b->id);
        });

        $errorMetaByBatch = $this->buildBatchErrorMetadata($allIds);

        $batches->getCollection()->transform(function (ImportBatch $batch) use ($errorMetaByBatch) {
            $meta = $errorMetaByBatch[$batch->id] ?? null;
            $batch->setAttribute(
                'error_tooltip',
                $meta['tooltip'] ?? ($batch->error_message ?: 'No row-level error details available.')
            );
            return $batch;
        });

        // Provide the 20 most-recent completed standard batches to power the parent-selector dropdown
        $recentStandardBatches = ImportBatch::topLevel()
            ->where('batch_type', 'standard')
            ->whereIn('status', ['completed', 'completed_with_errors'])
            ->latest()
            ->limit(20)
            ->get(['id', 'carrier_original_name', 'created_at']);

        return view('reconciliation.upload', compact('batches', 'recentStandardBatches'));
    }

    public function store(UploadBatchRequest $request)
    {
        abort_unless($request->user()?->can('create', ImportBatch::class), 403);

        $dto = new UploadBatchDTO(
            carrierFile: $request->file('carrier_file'),
            imsFile: $request->file('ims_file') ?? null,
            payeeFile: $request->file('payee_file') ?? null,
            healthSherpaFile: $request->file('health_sherpa_file') ?? null,
            duplicateStrategy: $request->duplicate_strategy,
            uploadedBy: auth()->id() ?? config('reconciliation.system_user_id', 1)
        );

        $batch = $this->reconciliationService->startSynchronization($dto);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            $batch->load('uploadedBy');

            return response()->json([
                'success' => true,
                'message' => 'Synchronization started. The ETL flow is now running in the background.',
                'batch' => $this->batchSerializer->serialize($batch, true, $request->user()),
            ], 202);
        }

        return redirect()
            ->route('reconciliation.upload.index')
            ->with('success', 'Files uploaded successfully and are processing in the background. Check back soon for results.');
    }

    public function rerun(RerunBatchRequest $request, ImportBatch $batch)
    {
        abort_unless($request->user()?->can('rerun', $batch), 403);

        if (in_array($batch->status, ['pending', 'processing'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This run is still in progress and cannot be rerun yet.',
            ], 422);
        }

        $rerunReason = trim((string) $request->input('rerun_reason', ''));

        if ($batch->batch_type === 'standard' && $batch->parent_batch_id === null) {
            $hasIms = $request->hasFile('ims_file') || !blank($batch->ims_file_path);
            $hasHs = $request->hasFile('health_sherpa_file') || !blank($batch->health_sherpa_file_path);
            if (!$hasIms && !$hasHs) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one source feed is required for rerun — IMS, Health Sherpa, or both.',
                ], 422);
            }

            $dto = new RetryBatchDTO(
                carrierFile: $request->file('carrier_file'),
                imsFile: $request->file('ims_file'),
                payeeFile: $request->file('payee_file'),
                healthSherpaFile: $request->file('health_sherpa_file'),
                duplicateStrategy: (string) ($request->input('duplicate_strategy') ?: ($batch->duplicate_strategy ?: 'skip')),
                uploadedBy: auth()->id() ?? config('reconciliation.system_user_id', 1),
                rerunReason: $rerunReason !== '' ? $rerunReason : null,
            );

            $rerunBatch = $this->reconciliationService->rerunSynchronization($batch, $dto);
            $rerunBatch->load('uploadedBy');

            return response()->json([
                'success' => true,
                'message' => 'Re-analysis started successfully. The selected run will be reprocessed in place and existing results will be replaced.',
                'batch' => $this->batchSerializer->serialize($rerunBatch, true, $request->user()),
            ], 202);
        }

        if ($batch->batch_type === 'contract_patch' && $batch->parent_batch_id !== null) {
            if ($batch->parentBatch && in_array($batch->parentBatch->status, ['pending', 'processing'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The parent standard synchronization is still in progress. Wait for it to finish before rerunning this contract patch.',
                ], 422);
            }

            $rerunBatch = $this->reconciliationService->rerunContractPatch(
                $batch,
                $request->file('contract_file'),
                auth()->id() ?? config('reconciliation.system_user_id', 1),
                $rerunReason !== '' ? $rerunReason : null,
            );
            $rerunBatch->load('uploadedBy');

            return response()->json([
                'success' => true,
                'message' => 'Contract patch re-analysis started successfully. Existing patch results will be replaced in place.',
                'batch' => $this->batchSerializer->serialize($rerunBatch, true, $request->user()),
            ], 202);
        }

        return response()->json([
            'success' => false,
            'message' => 'Only top-level standard runs and contract patch runs are eligible for rerun.',
        ], 422);
    }

    public function download(ImportBatch $batch)
    {
        abort_unless(auth()->user()?->can('downloadOutput', $batch), 403);

        abort_unless($batch->hasOutput(), 404, 'No output file available for this run.');

        $fullPath = Storage::disk('local')->path($batch->output_file_path);
        abort_unless(file_exists($fullPath), 404, 'Output file not found on disk.');

        $downloadName = 'Final_BOB_Output_' . $batch->created_at->format('Y-m-d') . '.xlsx';

        return response()->download($fullPath, $downloadName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function destroy(ImportBatch $batch)
    {
        abort_unless(auth()->user()?->can('delete', $batch), 403);

        $this->reconciliationService->deleteBatch($batch, auth()->id() ?? config('reconciliation.system_user_id', 1));

        return redirect()
            ->route('reconciliation.upload.index')
            ->with('success', 'Reconciliation run and all associated files (including contract patches) have been deleted.');
    }

    private function buildBatchErrorMetadata(Collection $batchIds): array
    {
        if ($batchIds->isEmpty())
            return [];

        $groupedErrors = ImportRowError::query()
            ->select('import_batch_id', 'error_type', 'field_name', 'error_messages')
            ->selectRaw('COUNT(*) as occurrences')
            ->whereIn('import_batch_id', $batchIds)
            ->groupBy('import_batch_id', 'error_type', 'field_name', 'error_messages')
            ->get();

        $result = [];

        foreach ($groupedErrors as $group) {
            $batchId = (string) $group->import_batch_id;
            $message = $this->normalizeRowErrorMessage($group->error_messages, $group->error_type, $group->field_name);
            $count = (int) $group->occurrences;

            if (!isset($result[$batchId])) {
                $result[$batchId] = ['total' => 0, 'reasons' => []];
            }

            $result[$batchId]['total'] += $count;
            $result[$batchId]['reasons'][$message] = ($result[$batchId]['reasons'][$message] ?? 0) + $count;
        }

        foreach ($result as $batchId => $meta) {
            arsort($meta['reasons']);
            $topReasons = [];
            foreach (array_slice($meta['reasons'], 0, 3, true) as $message => $count) {
                $topReasons[] = ['message' => $message, 'count' => $count];
            }
            $result[$batchId] = [
                'total' => $meta['total'],
                'tooltip' => $this->buildTooltipText($meta['total'], $topReasons),
            ];
        }

        return $result;
    }

    private function normalizeRowErrorMessage(mixed $errorMessages, ?string $errorType, ?string $fieldName): string
    {
        $decoded = is_array($errorMessages) ? $errorMessages : json_decode((string) $errorMessages, true);

        if (is_array($decoded)) {
            if (!empty($decoded['error']) && is_string($decoded['error'])) {
                return $decoded['error'];
            }
            $flatMessages = collect($decoded)->flatten()
                ->filter(fn($item) => is_scalar($item) && trim((string) $item) !== '')
                ->map(fn($item) => trim((string) $item))
                ->unique()->values();
            if ($flatMessages->isNotEmpty()) {
                return $flatMessages->implode('; ');
            }
        }

        return $fieldName
            ? sprintf('%s on %s', ucfirst($errorType ?? 'Validation'), $fieldName)
            : ucfirst($errorType ?: 'Validation error');
    }

    private function buildTooltipText(int $total, array $topReasons): string
    {
        if (empty($topReasons))
            return 'No row-level error details available.';

        $lines = ["Total failed rows: {$total}", 'Top reasons:'];
        foreach ($topReasons as $reason) {
            $lines[] = sprintf('- %dx %s', $reason['count'], $reason['message']);
        }
        return implode(PHP_EOL, $lines);
    }
}
