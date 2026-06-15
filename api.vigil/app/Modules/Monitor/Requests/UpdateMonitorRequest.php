<?php

namespace App\Modules\Monitor\Requests;

use App\Modules\Monitor\Enums\MonitorStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMonitorRequest extends FormRequest
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
            'target' => ['sometimes', 'required', 'string', 'max:2048'],
            'config' => ['sometimes', 'nullable', 'array'],
            'interval_seconds' => ['sometimes', 'required', 'integer', 'min:60', 'max:86400'],
            'timeout_ms' => ['sometimes', 'required', 'integer', 'min:1000', 'max:120000'],
            'confirmation_threshold' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            'recovery_threshold' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            'status' => ['sometimes', 'required', Rule::enum(MonitorStatus::class)],

            'config.expected_status' => ['nullable', 'integer', 'min:100', 'max:599'],
            'config.method' => ['nullable', 'string', 'in:GET,POST,HEAD,PUT,PATCH,DELETE'],
            'config.keyword' => ['nullable', 'string', 'max:255'],
            'config.keyword_should_be_present' => ['nullable', 'boolean'],
            'config.ssl_warn_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'config.dns_expected' => ['nullable', 'string', 'max:255'],
            'config.grace_period' => ['nullable', 'integer', 'min:30', 'max:604800'],
        ];
    }
}
