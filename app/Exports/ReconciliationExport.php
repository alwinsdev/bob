<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesSpreadsheetCells;
use App\Models\ReconciliationQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class ReconciliationExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;
    use SanitizesSpreadsheetCells;

    protected $status;
    protected $search;

    public function __construct($status = 'all', $search = null)
    {
        $this->status = $status;
        $this->search = $search;
    }

    public function query()
    {
        $query = ReconciliationQueue::query();

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('contract_id', 'like', '%' . $this->search . '%')
                  ->orWhere('member_first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('member_last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('carrier', 'like', '%' . $this->search . '%');
            });
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Status',
            'Contract ID',
            'Member Name',
            'Carrier',
            'Source/Method',
            'Match Score',
            'Aligned Agent',
            'Effective Date',
            'IMS ID',
            'Created At',
        ];
    }

    public function map($record): array
    {
        return $this->sanitizeRow([
            $record->id,
            ucfirst($record->status),
            $record->contract_id,
            trim($record->member_first_name . ' ' . $record->member_last_name),
            $record->carrier,
            $record->match_method_label,
            $record->match_confidence ? $record->match_confidence . '%' : 'N/A',
            $record->aligned_agent_name ?: '—',
            $record->effective_date ? $record->effective_date->format('m/d/Y') : '—',
            $record->ims_transaction_id ?: '—',
            $record->created_at->format('Y-m-d H:i:s'),
        ]);
    }
}
