<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('reconciliation.edit');
    }

    public function rules(): array
    {
        return [
            'aligned_agent_code' => ['required', 'string', 'exists:agents,agent_code'],
            'compensation_type' => ['required', 'in:New,Renewal'],
        ];
    }

    public function messages(): array
    {
        return [
            'aligned_agent_code.required' => 'Aligned Agent Code is required.',
            'aligned_agent_code.exists' => 'Aligned Agent Code was not found. Please use a valid code from the system.',
            'compensation_type.required' => 'Compensation Type is required.',
            'compensation_type.in' => 'Compensation Type must be New or Renewal.',
        ];
    }
}
