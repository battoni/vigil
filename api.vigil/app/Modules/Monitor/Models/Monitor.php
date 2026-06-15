<?php

namespace App\Modules\Monitor\Models;

use App\Modules\Incident\Models\Incident;
use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Enums\MonitorType;
use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Project\Models\Project;
use App\Modules\StatusPage\Models\StatusPage;
use Database\Factories\MonitorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'project_id', 'name', 'type', 'target', 'config', 'interval_seconds',
    'timeout_ms', 'confirmation_threshold', 'recovery_threshold', 'status',
    'consecutive_failures', 'consecutive_successes', 'heartbeat_token',
    'last_ping_at', 'last_checked_at', 'next_check_at', 'leased_until',
])]
class Monitor extends Model
{
    /** @use HasFactory<MonitorFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => MonitorType::class,
            'status' => MonitorStatus::class,
            'config' => 'array',
            'last_ping_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'next_check_at' => 'datetime',
            'leased_until' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function checkResults(): HasMany
    {
        return $this->hasMany(CheckResult::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(NotificationChannel::class, 'channel_monitor', 'monitor_id', 'channel_id')
            ->withPivot(['min_severity', 'notify_on_recovery'])
            ->withTimestamps();
    }

    public function statusPages(): BelongsToMany
    {
        return $this->belongsToMany(StatusPage::class, 'monitor_status_page')
            ->withPivot(['group_name', 'sort_order'])
            ->withTimestamps();
    }

    protected static function newFactory(): Factory
    {
        return MonitorFactory::new();
    }
}
