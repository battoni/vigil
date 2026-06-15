<?php

namespace Database\Factories;

use App\Modules\Incident\Models\Incident;
use App\Modules\Monitor\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'started_at' => now(),
            'resolved_at' => null,
            'cause' => 'timeout',
            'duration_seconds' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'resolved_at' => now(),
            'duration_seconds' => fake()->numberBetween(60, 3600),
        ]);
    }
}
