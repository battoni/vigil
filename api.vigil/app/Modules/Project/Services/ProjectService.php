<?php

namespace App\Modules\Project\Services;

use App\Modules\Project\DTOs\ProjectStoreDTO;
use App\Modules\Project\DTOs\ProjectUpdateDTO;
use App\Modules\Project\Models\Project;
use App\Modules\Project\Repositories\ProjectRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class ProjectService
{
    public function __construct(
        private ProjectRepository $projectRepository,
    ) {}

    /**
     * @return Project[]
     */
    public function list(): array
    {
        return $this->projectRepository->findAllProjects();
    }

    public function find(int $id): ?Project
    {
        return $this->projectRepository->findProjectById($id);
    }

    public function create(ProjectStoreDTO $dto): Project
    {
        $baseSlug = Str::slug($dto->name);
        $maxAttempts = 8;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $slug = $attempt === 0 ? $baseSlug : "{$baseSlug}-{$attempt}";

            try {
                $project = $this->projectRepository->createProject([
                    'name' => $dto->name,
                    'slug' => $slug,
                ]);

                return $this->findOrFail($project->id);
            } catch (QueryException $queryException) {
                if ($this->isSlugUniqueViolation($queryException)) {
                    continue;
                }

                throw $queryException;
            }
        }

        // Last resort: a guaranteed-unique slug.
        $project = $this->projectRepository->createProject([
            'name' => $dto->name,
            'slug' => "{$baseSlug}-".Str::lower(Str::random(6)),
        ]);

        return $this->findOrFail($project->id);
    }

    public function update(int $id, ProjectUpdateDTO $dto): Project
    {
        $updateData = array_filter([
            'name' => $dto->name,
        ], fn ($value) => $value !== null);

        if (! empty($updateData)) {
            $this->projectRepository->updateProject($id, $updateData);
        }

        return $this->findOrFail($id);
    }

    public function delete(int $id): bool
    {
        return $this->projectRepository->deleteProject($id);
    }

    private function findOrFail(int $id): Project
    {
        $project = $this->projectRepository->findProjectById($id);

        if (! $project) {
            throw new ModelNotFoundException('Project not found');
        }

        return $project;
    }

    private function isSlugUniqueViolation(QueryException $queryException): bool
    {
        $lowerMessage = strtolower($queryException->getMessage());
        $mentionsSlug = str_contains($lowerMessage, 'projects.slug') || str_contains($lowerMessage, 'projects`.`slug');
        $isUniqueOrDuplicate = str_contains($lowerMessage, 'unique') || str_contains($lowerMessage, 'duplicate');

        return $mentionsSlug && $isUniqueOrDuplicate;
    }
}
