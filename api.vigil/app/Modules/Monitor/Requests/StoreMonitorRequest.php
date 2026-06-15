<?php

namespace App\Modules\Monitor\Requests;

use App\Modules\Monitor\Enums\MonitorType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonitorRequest extends FormRequest
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
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(MonitorType::class)],
            'target' => ['required', 'string', 'max:2048'],
            'config' => ['nullable', 'array'],
            'interval_seconds' => ['required', 'integer', 'min:60', 'max:86400'],
            'timeout_ms' => ['nullable', 'integer', 'min:1000', 'max:120000'],
            'confirmation_threshold' => ['nullable', 'integer', 'min:1', 'max:10'],
            'recovery_threshold' => ['nullable', 'integer', 'min:1', 'max:10'],

            // Type-driven config (validated when present).
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
