<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'description' => ['nullable', 'string'],
            'permission_group_ids' => ['required', 'array', 'min:1'],
            'permission_group_ids.*' => ['required', 'string'],
        ];
    }
}
