<?php

namespace App\Modules\Incident\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Incident\Resources\IncidentResource;
use App\Modules\Incident\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function __construct(
        private IncidentService $incidentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $monitorId = $request->integer('monitor_id') ?: null;
        $onlyOpen = $request->has('open') ? $request->boolean('open') : null;

        $incidents = $this->incidentService->list($monitorId, $onlyOpen);

        return ApiResponse::success(data: IncidentResource::collection($incidents));
    }

    public function show(int $id): JsonResponse
    {
        $incident = $this->incidentService->find($id);

        return ! $incident ?
            ApiResponse::notFound(message: 'Incident not found') :
            ApiResponse::success(data: new IncidentResource($incident));
    }

    public function acknowledge(int $id, Request $request): JsonResponse
    {
        if (! $this->incidentService->find($id)) {
            return ApiResponse::notFound(message: 'Incident not found');
        }

        $incident = $this->incidentService->acknowledge($id, (int) $request->user()->id);

        return ApiResponse::success(data: new IncidentResource($incident));
    }
}
