<?php

namespace App\Modules\StatusPage\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\StatusPage\DTOs\StatusPageStoreDTO;
use App\Modules\StatusPage\DTOs\StatusPageUpdateDTO;
use App\Modules\StatusPage\Requests\RouteStatusMonitorRequest;
use App\Modules\StatusPage\Requests\StoreStatusPageRequest;
use App\Modules\StatusPage\Requests\UpdateStatusPageRequest;
use App\Modules\StatusPage\Resources\StatusPageResource;
use App\Modules\StatusPage\Services\StatusPageService;
use Illuminate\Http\JsonResponse;

class StatusPageController extends Controller
{
    public function __construct(
        private StatusPageService $statusPageService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(data: StatusPageResource::collection($this->statusPageService->list()));
    }

    public function show(int $id): JsonResponse
    {
        $statusPage = $this->statusPageService->find($id);

        return ! $statusPage ?
            ApiResponse::notFound(message: 'Status page not found') :
            ApiResponse::success(data: new StatusPageResource($statusPage));
    }

    public function store(StoreStatusPageRequest $request): JsonResponse
    {
        $statusPage = $this->statusPageService->create(StatusPageStoreDTO::from($request));

        return ApiResponse::created(data: new StatusPageResource($statusPage));
    }

    public function update(int $id, UpdateStatusPageRequest $request): JsonResponse
    {
        if (! $this->statusPageService->find($id)) {
            return ApiResponse::notFound(message: 'Status page not found');
        }

        $statusPage = $this->statusPageService->update($id, StatusPageUpdateDTO::from($request));

        return ApiResponse::success(data: new StatusPageResource($statusPage));
    }

    public function destroy(int $id): JsonResponse
    {
        $statusPage = $this->statusPageService->find($id);

        if (! $statusPage) {
            return ApiResponse::notFound(message: 'Status page not found');
        }

        $this->statusPageService->delete($id);

        return ApiResponse::success(data: new StatusPageResource($statusPage));
    }

    public function attachMonitor(int $id, int $monitorId, RouteStatusMonitorRequest $request): JsonResponse
    {
        if (! $this->statusPageService->find($id)) {
            return ApiResponse::notFound(message: 'Status page not found');
        }

        $pivot = [
            'group_name' => $request->input('group_name'),
            'sort_order' => (int) $request->integer('sort_order'),
        ];

        $statusPage = $this->statusPageService->attachMonitor($id, $monitorId, $pivot);

        return ApiResponse::success(data: new StatusPageResource($statusPage));
    }

    public function detachMonitor(int $id, int $monitorId): JsonResponse
    {
        if (! $this->statusPageService->find($id)) {
            return ApiResponse::notFound(message: 'Status page not found');
        }

        $statusPage = $this->statusPageService->detachMonitor($id, $monitorId);

        return ApiResponse::success(data: new StatusPageResource($statusPage));
    }
}
