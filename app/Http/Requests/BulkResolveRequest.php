<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkResolveRequest extends FormRequest
{
    private const DEFAULT_MAX_RECORDS = 100;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('reconciliation.bulk_approve');
    }

    public function rules(): array
    {
        $maxRecords = max(1, (int) config('reconciliation.bulk_resolve_max', self::DEFAULT_MAX_RECORDS));

        return [
            // Cap at 100 per operation: reduces transaction size, limits DoS potential,
            // and keeps lock contention manageable. See config reconciliation.bulk_resolve_max.
            'record_ids'         => ['required', 'array', 'min:1', 'max:' . $maxRecords],
            'record_ids.*'       => ['required', 'string', 'exists:reconciliation_queue,id'],
            'aligned_agent_code' => ['required', 'string', 'exists:agents,agent_code'],
            'compensation_type' => ['required', 'in:New,Renewal'],
        ];
    }

    public function messages(): array
    {
        return [
            'record_ids.required' => 'Please select at least one record for bulk resolution.',
            'record_ids.min' => 'Please select at least one record for bulk resolution.',
            'record_ids.max' => 'You can resolve up to ' . max(1, (int) config('reconciliation.bulk_resolve_max', self::DEFAULT_MAX_RECORDS)) . ' records at a time.',
            'record_ids.*.exists' => 'One or more selected records no longer exist. Please refresh and try again.',
            'aligned_agent_code.required' => 'Aligned Agent Code is required.',
            'aligned_agent_code.exists' => 'Aligned Agent Code was not found. Please use a valid code from the system.',
            'compensation_type.required' => 'Compensation Type is required.',
            'compensation_type.in' => 'Compensation Type must be New or Renewal.',
        ];
    }
}
