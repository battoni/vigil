<?php

namespace App\Modules\StatusPage\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatusPageRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'custom_domain' => ['nullable', 'string', 'max:255', 'unique:status_pages,custom_domain'],
            'branding' => ['nullable', 'array'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
