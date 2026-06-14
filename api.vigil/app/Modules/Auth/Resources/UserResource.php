<?php

namespace App\Modules\Auth\Resources;

use App\Modules\Auth\Enums\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'role' => $user->role?->name,
            'role_slug' => $user->role?->slug,
            'status' => $user->status?->value ?? UserStatus::ACTIVE->value,
            'permissions' => $user->getAttribute('permissions') ?? [],
        ];
    }
}
