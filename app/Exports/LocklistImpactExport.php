<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesSpreadsheetCells;
use App\Models\ReconciliationQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class LocklistImpactExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;
    use SanitizesSpreadsheetCells;

    protected $search;
    protected $batchId;

    public function __construct($search = null, $batchId = null)
    {
        $this->search = $search;
        $this->batchId = $batchId;
    }

    public function query()
    {
        $query = ReconciliationQueue::query()
            ->where('override_flag', true)
            ->notArchived();

        if ($this->batchId) {
            $query->where('import_batch_id', $this->batchId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('contract_id', 'like', '%' . $this->search . '%')
                  ->orWhere('member_first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('member_last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('aligned_agent_name', 'like', '%' . $this->search . '%')
                  ->orWhere('payee_name', 'like', '%' . $this->search . '%')
                  ->orWhere('group_team_sales', 'like', '%' . $this->search . '%');
            });
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'CONTRACT_ID',
            'MEMBER_NAME',
            'ORIGINAL_SOURCE',
            'ORIGINAL_AGENT',
            'LOCKED_AGENT',
            'DEPARTMENT',
            'STATUS',
        ];
    }

    public function map($row): array
    {
        return $this->sanitizeRow([
            $row->contract_id,
            trim($row->member_first_name . ' ' . $row->member_last_name),
            $row->original_match_method ?: 'Unmatched',
            $row->original_agent_name ?: '—',
            $row->aligned_agent_name,
            $row->group_team_sales,
            'RESOLVED',
        ]);
    }
}
