<?php

namespace App\Modules\Auth\Requests;

use App\Modules\Auth\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'role_id' => ['sometimes', 'nullable', 'integer', 'exists:roles,id'],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', 'boolean'],
        ];
    }
}
