<?php

namespace App\Events\Reconciliation;

use App\Models\ImportBatch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractPatchCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $batch;

    public function __construct(ImportBatch $batch)
    {
        $this->batch = $batch;
    }
}
