<?php

namespace App\Modules\Project\Repositories;

use App\Modules\Project\Models\Project;

class ProjectRepository
{
    public function __construct(
        private Project $model
    ) {}

    /**
     * @return Project[]
     */
    public function findAllProjects(): array
    {
        return $this->model->withCount('monitors')->orderBy('name')->get()->all();
    }

    public function findProjectById(int $id): ?Project
    {
        return $this->model->withCount('monitors')->find($id);
    }

    public function createProject(array $data): Project
    {
        return $this->model->create($data);
    }

    public function updateProject(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function deleteProject(int $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }
}
