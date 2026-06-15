<?php

namespace App\Modules\StatusPage\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'customDomain' => $this->custom_domain,
            'branding' => $this->branding ?? [],
            'isPublic' => (bool) $this->is_public,
            'monitorsCount' => $this->whenCounted('monitors'),
            'monitors' => $this->whenLoaded('monitors', fn () => $this->monitors->map(fn ($monitor) => [
                'id' => (string) $monitor->id,
                'name' => $monitor->name,
                'status' => $monitor->status->value,
                'groupName' => $monitor->pivot->group_name,
                'sortOrder' => $monitor->pivot->sort_order,
            ])),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
