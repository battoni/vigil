<?php

namespace App\Modules\Monitor\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Monitor\DTOs\MonitorStoreDTO;
use App\Modules\Monitor\DTOs\MonitorUpdateDTO;
use App\Modules\Monitor\Requests\StoreMonitorRequest;
use App\Modules\Monitor\Requests\UpdateMonitorRequest;
use App\Modules\Monitor\Resources\MonitorResource;
use App\Modules\Monitor\Services\MonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function __construct(
        private MonitorService $monitorService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $projectId = $request->integer('project_id') ?: null;
        $monitors = $this->monitorService->list($projectId);

        return ApiResponse::success(data: MonitorResource::collection($monitors));
    }

    public function show(int $id): JsonResponse
    {
        $monitor = $this->monitorService->find($id);

        return ! $monitor ?
            ApiResponse::notFound(message: 'Monitor not found') :
            ApiResponse::success(data: new MonitorResource($monitor));
    }

    public function store(StoreMonitorRequest $request): JsonResponse
    {
        $monitor = $this->monitorService->create(MonitorStoreDTO::from($request));

        return ApiResponse::created(data: new MonitorResource($monitor));
    }

    public function update(int $id, UpdateMonitorRequest $request): JsonResponse
    {
        if (! $this->monitorService->find($id)) {
            return ApiResponse::notFound(message: 'Monitor not found');
        }

        $monitor = $this->monitorService->update($id, MonitorUpdateDTO::from($request));

        return ApiResponse::success(data: new MonitorResource($monitor));
    }

    public function destroy(int $id): JsonResponse
    {
        $monitor = $this->monitorService->find($id);

        if (! $monitor) {
            return ApiResponse::notFound(message: 'Monitor not found');
        }

        $this->monitorService->delete($id);

        return ApiResponse::success(data: new MonitorResource($monitor));
    }
}
