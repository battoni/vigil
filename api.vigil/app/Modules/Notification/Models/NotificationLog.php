<?php

namespace App\Modules\Notification\Models;

use App\Modules\Incident\Enums\IncidentEvent;
use App\Modules\Incident\Models\Incident;
use App\Modules\Notification\Enums\NotificationStatus;
use Database\Factories\NotificationLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'incident_id', 'channel_id', 'event', 'status', 'error', 'sent_at',
])]
class NotificationLog extends Model
{
    /** @use HasFactory<NotificationLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'event' => IncidentEvent::class,
            'status' => NotificationStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(NotificationChannel::class, 'channel_id');
    }

    protected static function newFactory(): Factory
    {
        return NotificationLogFactory::new();
    }
}
