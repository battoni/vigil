<?php

namespace App\Modules\Monitor\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Monitor\Services\HeartbeatService;
use Illuminate\Http\JsonResponse;

class HeartbeatController extends Controller
{
    public function __construct(
        private HeartbeatService $heartbeatService
    ) {}

    public function ping(string $token): JsonResponse
    {
        $monitor = $this->heartbeatService->recordPing($token);

        return $monitor === null
            ? ApiResponse::notFound(message: 'Unknown heartbeat token')
            : ApiResponse::success(extraData: ['ok' => true]);
    }
}
