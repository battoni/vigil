<?php

namespace App\Modules\Auth\Resources;

use App\Modules\Auth\Helpers\PermissionGroupsHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $permissionsMap = $this->rolePermissions->pluck('value', 'permission_key')->toArray();
        $permissionGroupsStructure = PermissionGroupsHelper::getPermissionGroupsStructure();

        $permissionGroups = array_values(array_filter(
            array_map(function (array $group) use ($permissionsMap): ?array {
                $hasPermissions = collect($group['permissions'])
                    ->contains(fn (array $p) => array_key_exists($p['key'], $permissionsMap));

                if (! $hasPermissions) {
                    return null;
                }

                return [
                    'id' => $group['id'],
                    'nameKey' => $group['nameKey'],
                    'icon' => $group['icon'],
                    'permissions' => array_map(fn (array $p) => [
                        'labelKey' => $p['labelKey'],
                        'key' => $p['key'],
                        'value' => $permissionsMap[$p['key']] ?? false,
                    ], $group['permissions']),
                ];
            }, $permissionGroupsStructure),
            fn (?array $g) => $g !== null
        ));

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'permissionGroups' => $permissionGroups,
            'users' => $this->getAttribute('roleUsers') ?? [],
        ];
    }
}
