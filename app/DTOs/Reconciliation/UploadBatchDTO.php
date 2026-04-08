<?php

namespace App\DTOs\Reconciliation;

use Illuminate\Http\UploadedFile;

class UploadBatchDTO
{
    public function __construct(
        public readonly UploadedFile $carrierFile,
        public readonly ?UploadedFile $imsFile,
        public readonly ?UploadedFile $payeeFile,
        public readonly ?UploadedFile $healthSherpaFile,
        public readonly string $duplicateStrategy,
        public readonly int $uploadedBy
    ) {}
}
