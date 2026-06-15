<?php

namespace App\Modules\Notification\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelResource extends JsonResource
{
    /** Config keys whose values are masked in API output. */
    private const SECRET_KEYS = ['api_key', 'resend_api_key'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'config' => $this->maskedConfig(),
            'isActive' => (bool) $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function maskedConfig(): array
    {
        $config = $this->config ?? [];

        foreach (self::SECRET_KEYS as $key) {
            if (! empty($config[$key])) {
                $config[$key] = '••••••';
            }
        }

        return $config;
    }
}
