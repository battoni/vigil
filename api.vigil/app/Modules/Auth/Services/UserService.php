<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\DTOs\UserStoreDTO;
use App\Modules\Auth\DTOs\UserUpdateDTO;
use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Auth\Repositories\UserPermissionRepository;
use App\Modules\Auth\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Spatie\LaravelData\Optional;

class UserService
{
    public function __construct(
        private PermissionService $permissionService,
        private UserPermissionRepository $userPermissionRepository,
        private UserRepository $userRepository,
    ) {}

    /**
     * @param  array<int>|null  $ids  Filter by user ids.
     * @param  string|null  $orderBy  One of: name_asc, name_desc, status_asc, status_desc, role_asc, role_desc.
     */
    public function list(?array $ids = null, ?string $orderBy = null): array
    {
        $users = $this->userRepository->findAllUsers($ids, $orderBy);
        $permissionsMap = $this->permissionService->forUsers($users);

        foreach ($users as $user) {
            $user->setAttribute('permissions', $permissionsMap[$user->id] ?? []);
        }

        return $users;
    }

    public function find(int $id): ?User
    {
        $user = $this->userRepository->findUserById($id);

        if ($user) {
            $user->setAttribute('permissions', $this->permissionService->forUser($user));
        }

        return $user;
    }

    public function create(UserStoreDTO $dto): User
    {
        $user = $this->userRepository->createUser([
            'name' => $dto->name,
            'last_name' => $dto->last_name,
            'username' => $dto->username,
            'role_id' => $dto->role_id,
            'status' => $dto->status,
            'password' => Hash::make($dto->password),
        ]);

        if (! empty($dto->permissions)) {
            $this->userPermissionRepository->syncUserPermissions($user->id, $dto->permissions);
        }

        $user = $this->userRepository->findUserById($user->id);
        $user->setAttribute('permissions', $this->permissionService->forUser($user));

        return $user;
    }

    public function update(int $id, UserUpdateDTO $dto): ?User
    {
        $user = $this->userRepository->findUserById($id);

        if (! $user) {
            return null;
        }

        $data = [];

        $updateCandidates = [
            'name' => $dto->name,
            'last_name' => $dto->last_name,
            'username' => $dto->username,
            'role_id' => $dto->role_id,
            'status' => $dto->status,
            'password' => $dto->password,
        ];

        foreach ($updateCandidates as $field => $value) {
            if ($value instanceof Optional) continue;

            if ($field === 'password') {
                if ($value === null) continue;

                $data['password'] = Hash::make($value);

                continue;
            }

            $data[$field] = $value;
        }

        if (! empty($data)) {
            $this->userRepository->updateUser($id, $data);
        }

        if (! ($dto->permissions instanceof Optional)) {
            $this->userPermissionRepository->syncUserPermissions($id, $dto->permissions);
        }

        $this->permissionService->forgetUserMemo($id);

        $user = $this->userRepository->findUserById($id);
        $user->setAttribute('permissions', $this->permissionService->forUser($user));

        return $user;
    }

    public function delete(int $id): bool
    {
        return $this->userRepository->deleteUser($id);
    }

    public function archive(int $id): ?User
    {
        $user = $this->userRepository->findUserById($id);

        if (! $user) {
            return null;
        }

        $newStatus = $user->status === UserStatus::ACTIVE ? UserStatus::INACTIVE : UserStatus::ACTIVE;
        $this->userRepository->updateUser($id, ['status' => $newStatus->value]);

        $this->permissionService->forgetUserMemo($id);

        $user = $this->userRepository->findUserById($id);
        $user->setAttribute('permissions', $this->permissionService->forUser($user));

        return $user;
    }

    public function isUsernameAvailable(string $username, ?int $excludeId = null): bool
    {
        return $this->userRepository->findUserByUsername($username, $excludeId) === null;
    }
}
