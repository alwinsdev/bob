<?php

namespace App\DTOs\Reconciliation;

use Illuminate\Http\UploadedFile;

class ContractPatchDTO
{
    public function __construct(
        public readonly UploadedFile $contractFile,
        public readonly string $parentBatchId,
        public readonly int $uploadedBy
    ) {}
}
