<?php

namespace App\Modules\Notification\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'config' => ['sometimes', 'nullable', 'array'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
