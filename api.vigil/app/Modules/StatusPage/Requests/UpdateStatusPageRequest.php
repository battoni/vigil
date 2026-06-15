<?php

namespace App\Modules\StatusPage\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusPageRequest extends FormRequest
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
        $id = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'custom_domain' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('status_pages', 'custom_domain')->ignore($id)],
            'branding' => ['sometimes', 'nullable', 'array'],
            'is_public' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
