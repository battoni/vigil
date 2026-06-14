<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\DTOs\LoginCredentialsDTO;
use App\Modules\Auth\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private PermissionService $permissionService,
        private UserRepository $userRepository,
    ) {}

    public function authenticate(LoginCredentialsDTO $credentials, Request $request): User
    {
        $user = $this->userRepository->findUserByUsername($credentials->username);

        if (! $user) {
            throw ValidationException::withMessages([
                'username' => ['auth.login.usernameNotFound'],
            ]);
        }

        if (! Hash::check($credentials->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['auth.login.passwordIncorrect'],
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $user->setAttribute('permissions', $this->permissionService->forUser($user));

        return $user;
    }

    /**
     * @return array<int, string>
     */
    public function getSupportNames(): array
    {
        $activeUsers = $this->userRepository->findAllActiveUsers();
        if (empty($activeUsers)) {
            return [];
        }

        $permissionsByUserId = $this->permissionService->forUsers($activeUsers);
        $supportNames = [];

        foreach ($activeUsers as $activeUser) {
            $permissions = $permissionsByUserId[$activeUser->id] ?? [];
            $hasResetPasswordPermission = in_array('users.reset_password', $permissions, true);
            if (! $hasResetPasswordPermission) {
                continue;
            }

            $supportNames[] = trim("{$activeUser->name} {$activeUser->last_name}");
        }

        return array_values(array_filter($supportNames));
    }
}
