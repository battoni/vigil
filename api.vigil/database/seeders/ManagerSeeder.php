<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Auth\Helpers\PermissionGroupsHelper;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionGroups = PermissionGroupsHelper::getPermissionGroupsStructure();

        $role = Role::firstOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Manager',
                'description' => 'Access to all features',
            ]
        );

        $userRole = Role::firstOrCreate(
            ['slug' => 'user'],
            [
                'name' => 'User',
                'description' => 'No permissions profile',
            ]
        );

        $excludedUserPermissionKeys = [
            'users.search',
            'users.create',
            'users.update',
            'users.archive',
            'users.delete',
        ];

        $managerPermissions = [];
        $userPermissions = [];

        foreach ($permissionGroups as $group) {
            $managerGroupPermissions = $group['permissions'];
            $userGroupPermissions = $group['permissions'];

            if ($group['id'] === 'users') {
                $userGroupPermissions = array_values(array_filter(
                    $userGroupPermissions,
                    fn (array $permission) => ! in_array($permission['key'], $excludedUserPermissionKeys, true),
                ));
            }

            foreach ($managerGroupPermissions as $permission) {
                $managerPermissions[] = [
                    'role_id' => $role->id,
                    'permission_key' => $permission['key'],
                    'value' => true,
                ];
            }

            foreach ($userGroupPermissions as $permission) {
                $userPermissions[] = [
                    'role_id' => $userRole->id,
                    'permission_key' => $permission['key'],
                    'value' => true,
                ];
            }
        }

        RolePermission::where('role_id', $role->id)->delete();
        if (! empty($managerPermissions)) {
            RolePermission::insert($managerPermissions);
        }

        RolePermission::where('role_id', $userRole->id)->delete();
        if (! empty($userPermissions)) {
            RolePermission::insert($userPermissions);
        }

        User::firstOrCreate(
            ['username' => 'manager'],
            [
                'name' => 'Manager',
                'last_name' => 'Demo',
                'username' => 'manager',
                'role_id' => $role->id,
                'password' => Hash::make('password'),
            ]
        );

        User::firstOrCreate(
            ['username' => 'user_without_permissions'],
            [
                'name' => 'User',
                'last_name' => 'No Profile',
                'username' => 'user_without_permissions',
                'role_id' => $userRole->id,
                'password' => Hash::make('password'),
            ]
        );
    }
}
