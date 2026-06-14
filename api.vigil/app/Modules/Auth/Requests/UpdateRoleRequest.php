<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('roles')->ignore($roleId)],
            'description' => ['nullable', 'string'],
            'permission_group_ids' => ['sometimes', 'array', 'min:1'],
            'permission_group_ids.*' => ['required', 'string'],
        ];
    }
}
