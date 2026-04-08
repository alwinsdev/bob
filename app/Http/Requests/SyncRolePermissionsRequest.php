<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('access.manage');
    }

    public function rules(): array
    {
        return [
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'permission_names.*.exists' => 'One or more selected permissions are invalid.',
        ];
    }
}
