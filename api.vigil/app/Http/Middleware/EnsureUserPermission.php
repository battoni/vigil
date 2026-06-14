<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Modules\Auth\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserPermission
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::unauthorized(message: 'Unauthenticated');
        }

        $permissions = $this->permissionService->forUser($user);

        if (! in_array($permission, $permissions, true)) {
            return ApiResponse::forbidden(message: 'You do not have permission to perform this action');
        }

        return $next($request);
    }
}
