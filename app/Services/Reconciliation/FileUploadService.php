<?php

namespace App\Services\Reconciliation;

use App\Exceptions\Reconciliation\FileUploadException;
use App\Models\ImportBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * @throws FileUploadException
     */
    public function storeFile(UploadedFile $file, string $prefix): string
    {
        try {
            Storage::disk('local')->makeDirectory('imports');

            $filename = $prefix . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('imports', $filename, 'local');

            if (!$path) {
                throw new \Exception('Storage returned false when saving file.');
            }

            return $path;
        } catch (\Exception $e) {
            throw new FileUploadException("Failed to store uploaded file: " . $e->getMessage(), 0, $e);
        }
    }

    public function sanitizeFilename(string $filename): string
    {
        // 1. Strip path separators and null bytes first to prevent directory traversal
        $sanitized = str_replace(['/', '\\', '..', "\0"], '', $filename);

        // 2. Remove control characters (non-printable ASCII)
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/u', '', $sanitized) ?? '';

        // 3. Allow only safe characters: alphanumeric, hyphen, underscore, dot
        $sanitized = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $sanitized);

        // 4. Trim leading/trailing underscores left by the replacement above
        $sanitized = trim($sanitized, '_');

        return $sanitized !== '' ? Str::limit($sanitized, 255, '') : 'upload_file';
    }

    /**
     * Duplicate an existing stored import file into a new immutable retry asset.
     *
     * @throws FileUploadException
     */
    public function duplicateStoredFile(?string $existingPath, string $prefix): ?string
    {
        if (blank($existingPath)) {
            return null;
        }

        try {
            $disk = Storage::disk('local');
            if (!$disk->exists($existingPath)) {
                throw new \RuntimeException("Source file not found: {$existingPath}");
            }

            $disk->makeDirectory('imports');

            $extension = pathinfo($existingPath, PATHINFO_EXTENSION);
            $filename = $prefix . '_' . Str::uuid() . ($extension !== '' ? ".{$extension}" : '');
            $targetPath = 'imports/' . $filename;

            if (!$disk->copy($existingPath, $targetPath)) {
                throw new \RuntimeException('Storage copy returned false while duplicating file.');
            }

            return $targetPath;
        } catch (\Throwable $e) {
            throw new FileUploadException('Failed to duplicate source file for rerun: ' . $e->getMessage(), 0, $e);
        }
    }

    public function cleanupBatchAssets(ImportBatch $batch): void
    {
        $fileColumns = [
            'carrier_file_path',
            'ims_file_path',
            'payee_file_path',
            'health_sherpa_file_path',
            'contract_file_path',
            'output_file_path'
        ];

        foreach ($fileColumns as $col) {
            if ($batch->$col && Storage::disk('local')->exists($batch->$col)) {
                Storage::disk('local')->delete($batch->$col);
            }
        }
    }
}
