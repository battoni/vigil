<?php

namespace App\Modules\StatusPage\Services;

use App\Modules\StatusPage\DTOs\StatusPageStoreDTO;
use App\Modules\StatusPage\DTOs\StatusPageUpdateDTO;
use App\Modules\StatusPage\Models\StatusPage;
use App\Modules\StatusPage\Repositories\StatusPageRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class StatusPageService
{
    public function __construct(
        private StatusPageRepository $statusPageRepository,
    ) {}

    /**
     * @return StatusPage[]
     */
    public function list(): array
    {
        return $this->statusPageRepository->findAllStatusPages();
    }

    public function find(int $id): ?StatusPage
    {
        return $this->statusPageRepository->findStatusPageById($id);
    }

    public function publicBySlug(string $slug): ?StatusPage
    {
        return $this->statusPageRepository->findPublicBySlug($slug);
    }

    public function create(StatusPageStoreDTO $dto): StatusPage
    {
        $baseSlug = Str::slug($dto->name);

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $slug = $attempt === 0 ? $baseSlug : "{$baseSlug}-{$attempt}";

            try {
                $statusPage = $this->statusPageRepository->createStatusPage([
                    'name' => $dto->name,
                    'slug' => $slug,
                    'custom_domain' => $dto->custom_domain,
                    'branding' => $dto->branding,
                    'is_public' => $dto->is_public,
                ]);

                return $this->findOrFail($statusPage->id);
            } catch (QueryException $exception) {
                if ($this->isSlugCollision($exception)) {
                    continue;
                }

                throw $exception;
            }
        }

        $statusPage = $this->statusPageRepository->createStatusPage([
            'name' => $dto->name,
            'slug' => "{$baseSlug}-".Str::lower(Str::random(6)),
            'custom_domain' => $dto->custom_domain,
            'branding' => $dto->branding,
            'is_public' => $dto->is_public,
        ]);

        return $this->findOrFail($statusPage->id);
    }

    public function update(int $id, StatusPageUpdateDTO $dto): StatusPage
    {
        $data = $dto->toArray();

        if (! empty($data)) {
            $this->statusPageRepository->updateStatusPage($id, $data);
        }

        return $this->findOrFail($id);
    }

    public function delete(int $id): bool
    {
        return $this->statusPageRepository->deleteStatusPage($id);
    }

    public function attachMonitor(int $statusPageId, int $monitorId, array $pivot): StatusPage
    {
        $this->statusPageRepository->attachMonitor($statusPageId, $monitorId, $pivot);

        return $this->findOrFail($statusPageId);
    }

    public function detachMonitor(int $statusPageId, int $monitorId): StatusPage
    {
        $this->statusPageRepository->detachMonitor($statusPageId, $monitorId);

        return $this->findOrFail($statusPageId);
    }

    private function findOrFail(int $id): StatusPage
    {
        $statusPage = $this->statusPageRepository->findStatusPageById($id);

        if ($statusPage === null) {
            throw new ModelNotFoundException('Status page not found');
        }

        return $statusPage;
    }

    private function isSlugCollision(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'slug') && (str_contains($message, 'unique') || str_contains($message, 'duplicate'));
    }
}
