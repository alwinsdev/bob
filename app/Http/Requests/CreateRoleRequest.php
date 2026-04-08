<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('access.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.min' => 'Role name must be at least 3 characters.',
            'name.max' => 'Role name may not be greater than 50 characters.',
            'name.regex' => 'Role name may only contain letters, numbers, spaces, underscores, and hyphens.',
            'permission_names.*.exists' => 'One or more selected permissions are invalid.',
        ];
    }
}
