<?php

namespace App\Modules\StatusPage\Repositories;

use App\Modules\StatusPage\Models\StatusPage;

class StatusPageRepository
{
    public function __construct(
        private StatusPage $model
    ) {}

    /**
     * @return StatusPage[]
     */
    public function findAllStatusPages(): array
    {
        return $this->model->withCount('monitors')->orderBy('name')->get()->all();
    }

    public function findStatusPageById(int $id): ?StatusPage
    {
        return $this->model->with(['monitors' => $this->orderedMonitors()])->find($id);
    }

    public function findPublicBySlug(string $slug): ?StatusPage
    {
        return $this->model
            ->where('slug', $slug)
            ->where('is_public', true)
            ->with(['monitors' => $this->orderedMonitors()])
            ->first();
    }

    public function createStatusPage(array $data): StatusPage
    {
        return $this->model->create($data);
    }

    public function updateStatusPage(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function deleteStatusPage(int $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }

    public function attachMonitor(int $statusPageId, int $monitorId, array $pivot): void
    {
        $statusPage = $this->model->find($statusPageId);

        $statusPage?->monitors()->syncWithoutDetaching([$monitorId => $pivot]);
    }

    public function detachMonitor(int $statusPageId, int $monitorId): void
    {
        $statusPage = $this->model->find($statusPageId);

        $statusPage?->monitors()->detach($monitorId);
    }

    private function orderedMonitors(): callable
    {
        return fn ($query) => $query->orderBy('monitor_status_page.sort_order');
    }
}
