<?php

namespace App\Modules\Monitor\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'monitor_id', 'bucket_start', 'checks_total', 'checks_up',
    'uptime_ratio', 'p50_ms', 'p95_ms', 'max_ms',
])]
class MonitorUptimeHourly extends Model
{
    protected $table = 'monitor_uptime_hourly';

    protected function casts(): array
    {
        return [
            'bucket_start' => 'datetime',
            'uptime_ratio' => 'decimal:4',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
