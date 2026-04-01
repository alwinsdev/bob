<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import.upload');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,csv,xlsx', 'max:51200'],
            'type' => ['required', 'in:carrier,ims'],
            'duplicate_strategy' => ['required', 'in:skip,update'],
        ];
    }
}
