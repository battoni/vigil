<?php

namespace Database\Factories;

use App\Modules\Monitor\Enums\CheckOutcome;
use App\Modules\Monitor\Models\CheckResult;
use App\Modules\Monitor\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckResult>
 */
class CheckResultFactory extends Factory
{
    protected $model = CheckResult::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'checked_at' => now(),
            'result' => CheckOutcome::UP,
            'response_time_ms' => fake()->numberBetween(20, 800),
            'status_code' => 200,
            'error' => null,
            'region' => 'local',
        ];
    }

    public function down(): static
    {
        return $this->state(fn () => [
            'result' => CheckOutcome::DOWN,
            'response_time_ms' => null,
            'status_code' => 500,
            'error' => 'timeout',
        ]);
    }
}
