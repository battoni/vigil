<?php

namespace App\Modules\Project\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Project\DTOs\ProjectStoreDTO;
use App\Modules\Project\DTOs\ProjectUpdateDTO;
use App\Modules\Project\Requests\StoreProjectRequest;
use App\Modules\Project\Requests\UpdateProjectRequest;
use App\Modules\Project\Resources\ProjectResource;
use App\Modules\Project\Services\ProjectService;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    public function index(): JsonResponse
    {
        $projects = $this->projectService->list();

        return ApiResponse::success(data: ProjectResource::collection($projects));
    }

    public function show(int $id): JsonResponse
    {
        $project = $this->projectService->find($id);

        return ! $project ?
            ApiResponse::notFound(message: 'Project not found') :
            ApiResponse::success(data: new ProjectResource($project));
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create(ProjectStoreDTO::from($request));

        return ApiResponse::created(data: new ProjectResource($project));
    }

    public function update(int $id, UpdateProjectRequest $request): JsonResponse
    {
        if (! $this->projectService->find($id)) {
            return ApiResponse::notFound(message: 'Project not found');
        }

        $project = $this->projectService->update($id, ProjectUpdateDTO::from($request));

        return ApiResponse::success(data: new ProjectResource($project));
    }

    public function destroy(int $id): JsonResponse
    {
        $project = $this->projectService->find($id);

        if (! $project) {
            return ApiResponse::notFound(message: 'Project not found');
        }

        $this->projectService->delete($id);

        return ApiResponse::success(data: new ProjectResource($project));
    }
}
