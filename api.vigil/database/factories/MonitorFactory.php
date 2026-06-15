<?php

namespace Database\Factories;

use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Enums\MonitorType;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    protected $model = Monitor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->domainName(),
            'type' => MonitorType::HTTP,
            'target' => 'https://'.fake()->domainName(),
            'config' => ['expected_status' => 200],
            'interval_seconds' => 60,
            'timeout_ms' => 10000,
            'confirmation_threshold' => 2,
            'recovery_threshold' => 1,
            'status' => MonitorStatus::PENDING,
            'consecutive_failures' => 0,
            'consecutive_successes' => 0,
            'next_check_at' => now(),
        ];
    }

    public function up(): static
    {
        return $this->state(fn () => ['status' => MonitorStatus::UP]);
    }

    public function down(): static
    {
        return $this->state(fn () => ['status' => MonitorStatus::DOWN]);
    }

    public function paused(): static
    {
        return $this->state(fn () => ['status' => MonitorStatus::PAUSED, 'next_check_at' => null]);
    }

    public function heartbeat(): static
    {
        return $this->state(fn () => [
            'type' => MonitorType::HEARTBEAT,
            'target' => 'heartbeat',
            'heartbeat_token' => fake()->unique()->sha256(),
            'next_check_at' => null,
            'config' => ['grace_period' => 300],
        ]);
    }
}
