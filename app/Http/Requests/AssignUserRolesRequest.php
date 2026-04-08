<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('access.manage');
    }

    public function rules(): array
    {
        return [
            'role_names' => ['nullable', 'array'],
            'role_names.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'role_names.*.exists' => 'One or more selected roles are invalid.',
        ];
    }
}
