<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\DTOs\RolePermissionsUpdateDTO;
use App\Modules\Auth\DTOs\RoleStoreDTO;
use App\Modules\Auth\DTOs\RoleUpdateDTO;
use App\Modules\Auth\Helpers\PermissionGroupsHelper;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Repositories\RoleRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function __construct(
        private RoleRepository $roleRepository,
    ) {}

    /** @return Role[] */
    public function list(): array
    {
        $roles = $this->roleRepository->findAllRoles();

        $roleIds = array_map(fn (Role $r) => $r->id, $roles);
        $usersPerRole = $this->roleRepository->getUsersGroupedByRoleId($roleIds);

        foreach ($roles as $role) {
            $role->setAttribute('roleUsers', $usersPerRole[$role->id] ?? []);
        }

        return $roles;
    }

    public function find(int $id): ?Role
    {
        $role = $this->roleRepository->findRoleById($id);

        if (! $role) {
            return null;
        }

        $usersPerRole = $this->roleRepository->getUsersGroupedByRoleId([$role->id]);
        $role->setAttribute('roleUsers', $usersPerRole[$role->id] ?? []);

        return $role;
    }

    public function create(RoleStoreDTO $dto): Role
    {
        $permissionGroupsStructure = PermissionGroupsHelper::getPermissionGroupsStructure();
        $permissions = [];

        foreach ($dto->permission_group_ids as $groupId) {
            $group = collect($permissionGroupsStructure)->firstWhere('id', $groupId);
            if ($group) {
                foreach ($group['permissions'] as $permission) {
                    $permissions[$permission['key']] = false;
                }
            }
        }

        $baseSlug = Str::slug($dto->name);
        $maxAttempts = 8;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $slug = $attempt === 0 ? $baseSlug : "{$baseSlug}-{$attempt}";

            try {
                $role = $this->roleRepository->createRole([
                    'name' => $dto->name,
                    'description' => $dto->description,
                    'slug' => $slug,
                ]);

                $this->roleRepository->syncRolePermissions($role->id, $permissions);

                $role = $this->roleRepository->findRoleById($role->id);
                if (! $role) {
                    throw new ModelNotFoundException('Role not found');
                }

                $role->setAttribute('roleUsers', []);

                return $role;
            } catch (QueryException $queryException) {
                if ($this->isRolesSlugUniqueViolation($queryException)) {
                    continue;
                }

                if ($this->isRolesNameUniqueViolation($queryException)) {
                    throw ValidationException::withMessages([
                        'name' => ['The name has already been taken.'],
                    ]);
                }

                throw $queryException;
            }
        }

        throw ValidationException::withMessages([
            'name' => ['The name has already been taken.'],
        ]);
    }

    public function update(int $id, RoleUpdateDTO $dto): Role
    {
        $updateData = array_filter([
            'name' => $dto->name,
            'description' => $dto->description,
        ], fn ($v) => $v !== null);

        if (! empty($updateData)) {
            try {
                $this->roleRepository->updateRole($id, $updateData);
            } catch (QueryException $queryException) {
                if ($this->isRolesNameUniqueViolation($queryException)) {
                    throw ValidationException::withMessages([
                        'name' => ['The name has already been taken.'],
                    ]);
                }

                throw $queryException;
            }
        }

        if ($dto->permission_group_ids !== null) {
            $permissionGroupsStructure = PermissionGroupsHelper::getPermissionGroupsStructure();
            $existingPermissions = $this->roleRepository->getRolePermissions($id);
            $permissions = [];

            foreach ($dto->permission_group_ids as $groupId) {
                $group = collect($permissionGroupsStructure)->firstWhere('id', $groupId);
                if ($group) {
                    foreach ($group['permissions'] as $permission) {
                        $permissions[$permission['key']] = $existingPermissions[$permission['key']] ?? false;
                    }
                }
            }

            $this->roleRepository->syncRolePermissions($id, $permissions);
        }

        $role = $this->roleRepository->findRoleById($id);
        if (! $role) {
            throw new ModelNotFoundException('Role not found');
        }

        $usersPerRole = $this->roleRepository->getUsersGroupedByRoleId([$role->id]);
        $role->setAttribute('roleUsers', $usersPerRole[$role->id] ?? []);

        return $role;
    }

    public function updatePermissions(int $id, RolePermissionsUpdateDTO $dto): Role
    {
        $this->roleRepository->syncRolePermissions($id, $dto->permissions);

        $role = $this->roleRepository->findRoleById($id);
        if (! $role) {
            throw new ModelNotFoundException('Role not found');
        }

        $usersPerRole = $this->roleRepository->getUsersGroupedByRoleId([$role->id]);
        $role->setAttribute('roleUsers', $usersPerRole[$role->id] ?? []);

        return $role;
    }

    public function delete(int $id): bool
    {
        return $this->roleRepository->deleteRole($id);
    }

    private function isRolesSlugUniqueViolation(QueryException $queryException): bool
    {
        $lowerMessage = strtolower($queryException->getMessage());
        $mentionsSlug = str_contains($lowerMessage, 'roles.slug') || str_contains($lowerMessage, 'roles`.`slug');
        $isUniqueOrDuplicate = str_contains($lowerMessage, 'unique') || str_contains($lowerMessage, 'duplicate');

        return $mentionsSlug && $isUniqueOrDuplicate;
    }

    private function isRolesNameUniqueViolation(QueryException $queryException): bool
    {
        $lowerMessage = strtolower($queryException->getMessage());
        $mentionsName = str_contains($lowerMessage, 'roles.name') || str_contains($lowerMessage, 'roles`.`name');
        $isUniqueOrDuplicate = str_contains($lowerMessage, 'unique') || str_contains($lowerMessage, 'duplicate');

        return $mentionsName && $isUniqueOrDuplicate;
    }
}
