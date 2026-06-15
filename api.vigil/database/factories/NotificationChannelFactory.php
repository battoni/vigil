<?php

namespace Database\Factories;

use App\Modules\Notification\Enums\ChannelType;
use App\Modules\Notification\Models\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationChannel>
 */
class NotificationChannelFactory extends Factory
{
    protected $model = NotificationChannel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'type' => ChannelType::SLACK,
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/'.fake()->uuid()],
            'is_active' => true,
        ];
    }

    public function slack(): static
    {
        return $this->state(fn () => [
            'type' => ChannelType::SLACK,
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/'.fake()->uuid()],
        ]);
    }

    public function email(): static
    {
        return $this->state(fn () => [
            'type' => ChannelType::EMAIL,
            'config' => ['recipients' => [fake()->safeEmail()]],
        ]);
    }

    public function whatsapp(): static
    {
        return $this->state(fn () => [
            'type' => ChannelType::WHATSAPP,
            'config' => ['instance' => 'vigil', 'recipients' => [fake()->e164PhoneNumber()]],
        ]);
    }
}
