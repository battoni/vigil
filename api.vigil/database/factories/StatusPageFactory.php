<?php

namespace Database\Factories;

use App\Modules\StatusPage\Models\StatusPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StatusPage>
 */
class StatusPageFactory extends Factory
{
    protected $model = StatusPage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'custom_domain' => null,
            'branding' => ['headline' => fake()->catchPhrase()],
            'is_public' => true,
        ];
    }
}
