<?php

namespace App\Modules\Notification\Services;

use App\Modules\Notification\DTOs\ChannelStoreDTO;
use App\Modules\Notification\DTOs\ChannelUpdateDTO;
use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Notification\Repositories\ChannelRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\LaravelData\Optional;

class ChannelService
{
    public function __construct(
        private ChannelRepository $channelRepository,
    ) {}

    /**
     * @return NotificationChannel[]
     */
    public function list(): array
    {
        return $this->channelRepository->findAllChannels();
    }

    public function find(int $id): ?NotificationChannel
    {
        return $this->channelRepository->findChannelById($id);
    }

    public function create(ChannelStoreDTO $dto): NotificationChannel
    {
        return $this->channelRepository->createChannel([
            'name' => $dto->name,
            'type' => $dto->type,
            'config' => $dto->config,
            'is_active' => $dto->is_active,
        ]);
    }

    public function update(int $id, ChannelUpdateDTO $dto): NotificationChannel
    {
        $data = array_filter([
            'name' => $dto->name,
            'config' => $dto->config,
            'is_active' => $dto->is_active,
        ], fn ($value) => ! ($value instanceof Optional));

        if (! empty($data)) {
            $this->channelRepository->updateChannel($id, $data);
        }

        return $this->findOrFail($id);
    }

    public function delete(int $id): bool
    {
        return $this->channelRepository->deleteChannel($id);
    }

    public function attachMonitor(int $channelId, int $monitorId, array $pivot): NotificationChannel
    {
        $this->channelRepository->attachToMonitor($channelId, $monitorId, $pivot);

        return $this->findOrFail($channelId);
    }

    public function detachMonitor(int $channelId, int $monitorId): NotificationChannel
    {
        $this->channelRepository->detachFromMonitor($channelId, $monitorId);

        return $this->findOrFail($channelId);
    }

    private function findOrFail(int $id): NotificationChannel
    {
        $channel = $this->channelRepository->findChannelById($id);

        if ($channel === null) {
            throw new ModelNotFoundException('Channel not found');
        }

        return $channel;
    }
}
