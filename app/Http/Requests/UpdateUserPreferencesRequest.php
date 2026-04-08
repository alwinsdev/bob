<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'theme' => ['sometimes', 'in:dark,light'],
            'grid_density' => ['sometimes', 'in:compact,normal,comfortable'],
            'compact_sidebar' => ['sometimes', 'boolean'],
            'email_notifications' => ['sometimes', 'boolean'],
            'auto_refresh' => ['sometimes', 'boolean'],
            'export_format' => ['sometimes', 'in:xlsx,csv,pdf'],
            'page_size' => ['sometimes', 'integer', 'in:25,50,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'theme.in' => 'Theme must be dark or light.',
            'grid_density.in' => 'Grid density must be compact, normal, or comfortable.',
            'export_format.in' => 'Export format must be XLSX, CSV, or PDF.',
            'page_size.in' => 'Page size must be 25, 50, or 100.',
        ];
    }
}
