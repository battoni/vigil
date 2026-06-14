<?php

namespace App\Modules\Auth\Repositories;

use App\Models\User;
use App\Modules\Auth\Enums\UserStatus;

class UserRepository
{
    public function __construct(
        private User $model
    ) {}

    /**
     * @param  array<int>|null  $ids  Filter by user ids (when provided and non-empty).
     * @param  string|null  $orderBy  One of: name_asc, name_desc, status_asc, status_desc, role_asc, role_desc.
     */
    public function findAllUsers(?array $ids = null, ?string $orderBy = null): array
    {
        $query = $this->model->newQuery();

        if ($ids !== null && count($ids) > 0) {
            $query->whereIn('id', $ids);
        }

        $orderBy = $orderBy ?? 'name_asc';
        switch ($orderBy) {
            case 'name_desc':
                $query->orderByDesc('name')->orderByDesc('last_name');
                break;
            case 'status_asc':
                $query->orderBy('status');
                break;
            case 'status_desc':
                $query->orderByDesc('status');
                break;
            case 'role_asc':
                $query
                    ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                    ->select('users.*')
                    ->orderBy('roles.name')
                    ->orderBy('users.name')
                    ->orderBy('users.last_name');
                break;
            case 'role_desc':
                $query
                    ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                    ->select('users.*')
                    ->orderByDesc('roles.name')
                    ->orderBy('users.name')
                    ->orderBy('users.last_name');
                break;
            case 'name_asc':
            default:
                $query->orderBy('name')->orderBy('last_name');
                break;
        }

        return $query->get()->all();
    }

    public function findUserById(int $id): ?User
    {
        return $this->model->find($id);
    }

    /**
     * @return array<int, User>
     */
    public function findAllActiveUsers(): array
    {
        return $this->model
            ->newQuery()
            ->where('status', UserStatus::ACTIVE->value)
            ->orderBy('name')
            ->orderBy('last_name')
            ->get()
            ->all();
    }

    public function findUserByUsername(string $username, ?int $excludeId = null): ?User
    {
        $query = $this->model->where('username', $username);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function createUser(array $data): User
    {
        return $this->model->create($data);
    }

    public function updateUser(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function deleteUser(int $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }
}
