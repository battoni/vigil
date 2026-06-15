<?php

namespace Database\Factories;

use App\Modules\Monitor\Models\MaintenanceWindow;
use App\Modules\Monitor\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceWindow>
 */
class MaintenanceWindowFactory extends Factory
{
    protected $model = MaintenanceWindow::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addHour(),
            'reason' => fake()->sentence(),
        ];
    }

    public function global(): static
    {
        return $this->state(fn () => ['monitor_id' => null]);
    }
}
