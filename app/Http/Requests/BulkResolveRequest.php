<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkResolveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reconciliation.bulk_approve');
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
}
