<?php

namespace App\Modules\Notification\Models;

use App\Modules\Monitor\Models\Monitor;
use App\Modules\Notification\Enums\ChannelType;
use Database\Factories\NotificationChannelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'type', 'config', 'is_active'])]
class NotificationChannel extends Model
{
    /** @use HasFactory<NotificationChannelFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class, 'channel_monitor', 'channel_id', 'monitor_id')
            ->withPivot(['min_severity', 'notify_on_recovery'])
            ->withTimestamps();
    }

    protected static function newFactory(): Factory
    {
        return NotificationChannelFactory::new();
    }
}
