<?php

namespace Database\Factories;

use App\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
        ];
    }
}
