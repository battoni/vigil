<?php

namespace App\Modules\StatusPage\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\StatusPage\Resources\PublicStatusResource;
use App\Modules\StatusPage\Services\StatusPageService;
use Illuminate\Http\JsonResponse;

class PublicStatusController extends Controller
{
    public function __construct(
        private StatusPageService $statusPageService
    ) {}

    public function show(string $slug): JsonResponse
    {
        $statusPage = $this->statusPageService->publicBySlug($slug);

        return ! $statusPage ?
            ApiResponse::notFound(message: 'Status page not found') :
            ApiResponse::success(data: new PublicStatusResource($statusPage));
    }
}
