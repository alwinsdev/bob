<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesSpreadsheetCells;
use App\Models\LockList;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class LockListExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;
    use SanitizesSpreadsheetCells;

    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function query()
    {
        $query = LockList::query()->orderBy('policy_id');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('policy_id', 'like', '%' . $this->search . '%')
                  ->orWhere('agent_name', 'like', '%' . $this->search . '%')
                  ->orWhere('department', 'like', '%' . $this->search . '%')
                  ->orWhere('payee_name', 'like', '%' . $this->search . '%');
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'CONTRACT_ID',
            'AGENT_NAME',
            'DEPARTMENT_NAME',
            'PAYEE_NAME',
            'CREATED_AT',
            'UPDATED_AT',
        ];
    }

    public function map($row): array
    {
        return $this->sanitizeRow([
            $row->policy_id,
            $row->agent_name,
            $row->department,
            $row->payee_name,
            $row->created_at?->format('Y-m-d'),
            $row->updated_at?->format('Y-m-d H:i'),
        ]);
    }
}
