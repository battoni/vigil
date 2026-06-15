<?php

namespace Database\Factories;

use App\Modules\Incident\Enums\IncidentEvent;
use App\Modules\Incident\Models\Incident;
use App\Modules\Notification\Enums\NotificationStatus;
use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Notification\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'incident_id' => Incident::factory(),
            'channel_id' => NotificationChannel::factory(),
            'event' => IncidentEvent::DOWN,
            'status' => NotificationStatus::SENT,
            'error' => null,
            'sent_at' => now(),
        ];
    }
}
