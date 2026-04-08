<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkResolveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('reconciliation.bulk_approve');
    }

    public function rules(): array
    {
        return [
            'record_ids' => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['required', 'string', 'exists:reconciliation_queue,id'],
            'aligned_agent_code' => ['required', 'string', 'exists:agents,agent_code'],
            'compensation_type' => ['required', 'in:New,Renewal'],
        ];
    }

    public function messages(): array
    {
        return [
            'record_ids.required' => 'Please select at least one record for bulk resolution.',
            'record_ids.min' => 'Please select at least one record for bulk resolution.',
            'record_ids.max' => 'You can resolve up to 500 records at a time.',
            'record_ids.*.exists' => 'One or more selected records no longer exist. Please refresh and try again.',
            'aligned_agent_code.required' => 'Aligned Agent Code is required.',
            'aligned_agent_code.exists' => 'Aligned Agent Code was not found. Please use a valid code from the system.',
            'compensation_type.required' => 'Compensation Type is required.',
            'compensation_type.in' => 'Compensation Type must be New or Renewal.',
        ];
    }
}
