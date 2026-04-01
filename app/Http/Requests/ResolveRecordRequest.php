<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reconciliation.edit');
    }

    public function rules(): array
    {
        return [
            'aligned_agent_code' => ['required', 'string', 'exists:agents,agent_code'],
            'compensation_type' => ['required', 'in:New,Renewal'],
        ];
    }
}
