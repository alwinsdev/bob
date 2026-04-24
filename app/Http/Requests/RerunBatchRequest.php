<?php

namespace App\Http\Requests;

use App\Models\ImportBatch;
use App\Rules\ValidUploadSignature;
use Illuminate\Foundation\Http\FormRequest;

class RerunBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $batch = $this->route('batch');
        if ($batch instanceof ImportBatch) {
            return (bool) $user->can('rerun', $batch);
        }

        return (bool) $user->can('reconciliation.reanalysis.run');
    }

    public function rules(): array
    {
        return [
            'duplicate_strategy' => ['nullable', 'in:skip,update'],
            'rerun_reason' => ['nullable', 'string', 'max:500'],
            'carrier_file' => ['nullable', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200', new ValidUploadSignature('Carrier Feed')],
            'ims_file' => ['nullable', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200', new ValidUploadSignature('IMS Agent Data')],
            'payee_file' => ['nullable', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200', new ValidUploadSignature('Agency Payee Details')],
            'health_sherpa_file' => ['nullable', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200', new ValidUploadSignature('Health Sherpa')],
            'contract_file' => ['nullable', 'file', 'mimes:csv,txt,xlsx,xls', 'max:30720', new ValidUploadSignature('Contract patch')],
        ];
    }

    public function messages(): array
    {
        return [
            'duplicate_strategy.in' => 'Duplicate strategy must be skip or update.',
            'carrier_file.mimes' => 'Carrier file must be a valid CSV or Excel (.xlsx / .xls) file.',
            'ims_file.mimes' => 'IMS file must be a valid CSV or Excel file.',
            'payee_file.mimes' => 'Payee file must be a valid CSV or Excel file.',
            'health_sherpa_file.mimes' => 'Health Sherpa file must be a valid CSV or Excel file.',
            'contract_file.mimes' => 'Contract patch file must be a valid CSV or Excel file.',
            'rerun_reason.max' => 'Rerun reason may not exceed 500 characters.',
        ];
    }
}
