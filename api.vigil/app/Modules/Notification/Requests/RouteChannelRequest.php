<?php

namespace App\Modules\Notification\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RouteChannelRequest extends FormRequest
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
            'min_severity' => ['nullable', 'string', 'in:info,warning,critical'],
            'notify_on_recovery' => ['nullable', 'boolean'],
        ];
    }
}
