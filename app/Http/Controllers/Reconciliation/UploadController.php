<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadBatchRequest;
use App\Models\ImportBatch;
use App\Jobs\ProcessImportBatchJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function index()
    {
        $batches = ImportBatch::with('uploadedBy')->latest()->paginate(20);
        return view('reconciliation.upload', compact('batches'));
    }

    public function store(UploadBatchRequest $request)
    {
        $file = $request->file('file');
        
        if (!Storage::disk('local')->exists('imports')) {
            Storage::disk('local')->makeDirectory('imports');
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('imports', $filename, 'local');

        // Create Batch
        $batch = ImportBatch::create([
            'file_name' => $path,
            'original_name' => $file->getClientOriginalName(),
            'type' => $request->type,
            'duplicate_strategy' => $request->duplicate_strategy,
            'status' => 'pending',
            'uploaded_by' => auth()->id(),
        ]);

        $absolutePath = Storage::disk('local')->path($path);
        
        ProcessImportBatchJob::dispatch($batch, $absolutePath);

        return redirect()->route('reconciliation.upload.index')->with('success', 'File uploaded and is processing in the background.');
    }
}
