<?php

namespace App\Modules\Monitor\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Monitor\DTOs\MonitorStoreDTO;
use App\Modules\Monitor\DTOs\MonitorUpdateDTO;
use App\Modules\Monitor\Requests\StoreMonitorRequest;
use App\Modules\Monitor\Requests\UpdateMonitorRequest;
use App\Modules\Monitor\Resources\CheckResultResource;
use App\Modules\Monitor\Resources\MonitorResource;
use App\Modules\Monitor\Services\MonitorMetricsService;
use App\Modules\Monitor\Services\MonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonitorController extends Controller
{
    public function __construct(
        private MonitorService $monitorService,
        private MonitorMetricsService $monitorMetricsService,
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

    public function uptime(int $id): JsonResponse
    {
        if (! $this->monitorService->find($id)) {
            return ApiResponse::notFound(message: 'Monitor not found');
        }

        return ApiResponse::success(data: $this->monitorMetricsService->uptime($id));
    }

    public function checks(int $id, Request $request): JsonResponse
    {
        if (! $this->monitorService->find($id)) {
            return ApiResponse::notFound(message: 'Monitor not found');
        }

        $since = $request->filled('since') ? Carbon::parse((string) $request->string('since')) : null;
        $checks = $this->monitorMetricsService->recentChecks($id, $since);

        return ApiResponse::success(data: CheckResultResource::collection($checks));
    }

    public function series(int $id, Request $request): JsonResponse
    {
        if (! $this->monitorService->find($id)) {
            return ApiResponse::notFound(message: 'Monitor not found');
        }

        $series = $this->monitorMetricsService->series($id, (string) $request->string('range', '7d'));

        return ApiResponse::success(data: $series);
    }
}
