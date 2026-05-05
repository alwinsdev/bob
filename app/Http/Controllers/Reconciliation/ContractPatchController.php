<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\DTOs\Reconciliation\ContractPatchDTO;
use App\Rules\ValidUploadSignature;
use App\Services\Reconciliation\ContractPatchService;
use App\Services\Reconciliation\BatchSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContractPatchController extends Controller
{
    public function __construct(
        private readonly ContractPatchService $contractPatchService,
        private readonly BatchSerializer $batchSerializer
    ) {}

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user()?->can('create', ImportBatch::class), 403);

        $request->validate([
            'contract_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:30720', new ValidUploadSignature('Contract patch')],
            'parent_batch_id' => ['nullable', 'ulid', 'exists:import_batches,id'],
        ]);

        $latestProcessedParent = ImportBatch::query()
            ->topLevel()
            ->where('batch_type', 'standard')
            ->whereIn('status', ['completed', 'completed_with_errors'])
            ->latest('created_at')
            ->first();

        if (!$latestProcessedParent) {
            return response()->json([
                'success' => false,
                'message' => 'No processed Final BOB run is available yet. Run a standard synchronization first, then upload the contract file.',
            ], 422);
        }

        $dto = new ContractPatchDTO(
            contractFile: $request->file('contract_file'),
            parentBatchId: $latestProcessedParent->id,
            uploadedBy: auth()->id() ?? config('reconciliation.system_user_id', 1)
        );

        $batch = $this->contractPatchService->startContractPatch($dto);

        $batch->load('uploadedBy');

        return response()->json([
            'success' => true,
            'message' => 'Payee Back-Flow Analysis started. Results will appear in the "Associated Contract Patches" section of the related BOB run.',
            'batch'   => $this->batchSerializer->serialize($batch, true, $request->user()),
            'target_parent_batch_id' => $latestProcessedParent->id,
        ], 202);
    }

    public function download(ImportBatch $batch): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('downloadOutput', $batch), 403);

        abort_unless($batch->batch_type === 'contract_patch', 404, 'This is not a contract patch batch.');
        abort_unless($batch->hasOutput(), 404, 'No output file available for this patch run.');

        $fullPath = Storage::disk('local')->path($batch->output_file_path);
        abort_unless(file_exists($fullPath), 404, 'Output file not found on disk.');

        $downloadName = 'Payee_Analysis_' . $batch->created_at->format('Y-m-d') . '.xlsx';

        return response()->download($fullPath, $downloadName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function destroy(ImportBatch $batch)
    {
        abort_unless(auth()->user()?->can('delete', $batch), 403);

        abort_unless($batch->batch_type === 'contract_patch', 403, 'Only contract patches can be deleted via this endpoint.');

        $this->contractPatchService->deletePatch($batch, auth()->id() ?? config('reconciliation.system_user_id', 1));

        return response()->json([
            'success' => true,
            'message' => 'Contract patch record deleted successfully.',
        ]);
    }
}
