<?php

namespace App\Modules\Auth\Requests;

use App\Modules\Auth\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'password' => ['required', 'string', 'min:8'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', 'boolean'],
        ];
    }
}
