<?php

namespace App\Modules\StatusPage\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RouteStatusMonitorRequest extends FormRequest
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
            'group_name' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
