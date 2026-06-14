<?php

namespace App\Modules\Auth\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Auth\DTOs\UserStoreDTO;
use App\Modules\Auth\DTOs\UserUpdateDTO;
use App\Modules\Auth\Requests\StoreUserRequest;
use App\Modules\Auth\Requests\UpdateUserRequest;
use App\Modules\Auth\Resources\UserResource;
use App\Modules\Auth\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ids = $request->query('ids');
        $ids = is_array($ids) ? array_map('intval', $ids) : (is_string($ids) ? array_map('intval', array_filter(explode(',', $ids))) : null);
        $ids = ! empty($ids) ? $ids : null;

        $orderBy = $request->query('order_by');
        $orderBy = is_string($orderBy) ? $orderBy : null;

        $users = $this->userService->list($ids, $orderBy);

        return ApiResponse::success(data: UserResource::collection($users));
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->find($id);

        return ! $user
            ? ApiResponse::notFound(message: 'User not found')
            : ApiResponse::success(data: new UserResource($user));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $dto = UserStoreDTO::from($request);
        $user = $this->userService->create($dto);

        return ApiResponse::created(data: new UserResource($user));
    }

    public function update(int $id, UpdateUserRequest $request): JsonResponse
    {
        $dto = UserUpdateDTO::from($request);
        $user = $this->userService->update($id, $dto);

        return ! $user
            ? ApiResponse::notFound(message: 'User not found')
            : ApiResponse::success(data: new UserResource($user));
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->find($id);

        if (! $user) {
            return ApiResponse::notFound(message: 'User not found');
        }

        $this->userService->delete($id);

        return ApiResponse::success(data: new UserResource($user));
    }

    public function archive(int $id): JsonResponse
    {
        $user = $this->userService->archive($id);

        return ! $user
            ? ApiResponse::notFound(message: 'User not found')
            : ApiResponse::success(data: new UserResource($user));
    }

    public function checkUsername(Request $request): JsonResponse
    {
        $username = $request->query('username');
        $excludeId = $request->query('exclude_id');

        if (! is_string($username) || $username === '') {
            return ApiResponse::badRequest(message: 'Username is required');
        }

        $excludeId = is_numeric($excludeId) ? (int) $excludeId : null;
        $available = $this->userService->isUsernameAvailable($username, $excludeId);

        return $available
            ? ApiResponse::success(data: ['available' => true])
            : ApiResponse::conflict(message: 'Username is already taken');
    }
}
