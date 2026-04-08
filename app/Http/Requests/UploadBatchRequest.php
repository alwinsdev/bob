<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', \App\Models\ImportBatch::class);
    }

    public function rules(): array
    {
        return [
            // Carrier feed is always required
            'carrier_file'        => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200'],
            'duplicate_strategy'  => ['required', 'in:skip,update'],

            // At least one of IMS or Health Sherpa must be provided (enforced in withValidator)
            'ims_file'            => ['nullable', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200'],
            'payee_file'          => ['nullable', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200'],
            'health_sherpa_file'  => ['nullable', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasIms = $this->hasFile('ims_file');
            $hasHs  = $this->hasFile('health_sherpa_file');

            if (!$hasIms && !$hasHs) {
                $validator->errors()->add(
                    'source_file',
                    'At least one source feed is required — please upload an IMS Agent Data file, a Health Sherpa file, or both.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'carrier_file.required' => 'The Carrier Feed (BOB) file is required to run a synchronization.',
            'carrier_file.mimes'    => 'The Carrier Feed must be a valid CSV or Excel (.xlsx / .xls) file.',
            'carrier_file.max'      => 'The Carrier Feed file may not exceed 50MB.',
            'ims_file.mimes'        => 'The IMS Agent Data file must be a valid CSV or Excel file.',
            'payee_file.mimes'      => 'The Agency Payee Details file must be a valid CSV or Excel file.',
            'health_sherpa_file.mimes' => 'The Health Sherpa file must be a valid CSV or Excel file.',
        ];
    }
}
