<?php

namespace App\Modules\Monitor\Models;

use App\Modules\Monitor\Enums\CheckOutcome;
use Database\Factories\CheckResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * High-volume, partitioned table (MySQL). No created_at/updated_at — checked_at
 * is the authoritative timestamp and the partition key.
 */
#[Fillable([
    'monitor_id', 'checked_at', 'result', 'response_time_ms',
    'status_code', 'error', 'region',
])]
class CheckResult extends Model
{
    /** @use HasFactory<CheckResultFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'result' => CheckOutcome::class,
            'checked_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    protected static function newFactory(): Factory
    {
        return CheckResultFactory::new();
    }
}
