<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Services\Reconciliation\BatchSerializer;
use Illuminate\Http\Request;

class BatchStatusController extends Controller
{
    public function __construct(
        private readonly BatchSerializer $batchSerializer
    ) {}

    public function status(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['sometimes', 'array', 'max:50'],
            'ids.*' => ['required', 'ulid'],
        ]);

        $ids = array_values(array_unique($validated['ids'] ?? []));

        if (empty($ids)) {
            return response()->json([]);
        }

        $batches = ImportBatch::whereIn('id', $ids)->get();

        $data = $batches->map(function (ImportBatch $batch) use ($request) {
            return $this->batchSerializer->serialize($batch, true, $request->user());
        });

        return response()->json($data);
    }
}
