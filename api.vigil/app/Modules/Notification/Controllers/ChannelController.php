<?php

namespace App\Modules\Notification\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Notification\DTOs\ChannelStoreDTO;
use App\Modules\Notification\DTOs\ChannelUpdateDTO;
use App\Modules\Notification\Requests\RouteChannelRequest;
use App\Modules\Notification\Requests\StoreChannelRequest;
use App\Modules\Notification\Requests\UpdateChannelRequest;
use App\Modules\Notification\Resources\ChannelResource;
use App\Modules\Notification\Services\ChannelService;
use Illuminate\Http\JsonResponse;

class ChannelController extends Controller
{
    public function __construct(
        private ChannelService $channelService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(data: ChannelResource::collection($this->channelService->list()));
    }

    public function show(int $id): JsonResponse
    {
        $channel = $this->channelService->find($id);

        return ! $channel ?
            ApiResponse::notFound(message: 'Channel not found') :
            ApiResponse::success(data: new ChannelResource($channel));
    }

    public function store(StoreChannelRequest $request): JsonResponse
    {
        $channel = $this->channelService->create(ChannelStoreDTO::from($request));

        return ApiResponse::created(data: new ChannelResource($channel));
    }

    public function update(int $id, UpdateChannelRequest $request): JsonResponse
    {
        if (! $this->channelService->find($id)) {
            return ApiResponse::notFound(message: 'Channel not found');
        }

        $channel = $this->channelService->update($id, ChannelUpdateDTO::from($request));

        return ApiResponse::success(data: new ChannelResource($channel));
    }

    public function destroy(int $id): JsonResponse
    {
        $channel = $this->channelService->find($id);

        if (! $channel) {
            return ApiResponse::notFound(message: 'Channel not found');
        }

        $this->channelService->delete($id);

        return ApiResponse::success(data: new ChannelResource($channel));
    }

    public function attachMonitor(int $id, int $monitorId, RouteChannelRequest $request): JsonResponse
    {
        if (! $this->channelService->find($id)) {
            return ApiResponse::notFound(message: 'Channel not found');
        }

        $pivot = [
            'min_severity' => $request->input('min_severity'),
            'notify_on_recovery' => $request->boolean('notify_on_recovery', true),
        ];

        $channel = $this->channelService->attachMonitor($id, $monitorId, $pivot);

        return ApiResponse::success(data: new ChannelResource($channel));
    }

    public function detachMonitor(int $id, int $monitorId): JsonResponse
    {
        if (! $this->channelService->find($id)) {
            return ApiResponse::notFound(message: 'Channel not found');
        }

        $channel = $this->channelService->detachMonitor($id, $monitorId);

        return ApiResponse::success(data: new ChannelResource($channel));
    }
}
