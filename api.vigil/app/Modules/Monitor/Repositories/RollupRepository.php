<?php

namespace App\Modules\Monitor\Repositories;

use App\Modules\Monitor\Models\MonitorUptimeDaily;
use App\Modules\Monitor\Models\MonitorUptimeHourly;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RollupRepository
{
    private const UPDATE_COLUMNS = [
        'checks_total', 'checks_up', 'uptime_ratio', 'p50_ms', 'p95_ms', 'max_ms', 'updated_at',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function upsertHourly(array $rows): void
    {
        if (! empty($rows)) {
            MonitorUptimeHourly::upsert($rows, ['monitor_id', 'bucket_start'], self::UPDATE_COLUMNS);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function upsertDaily(array $rows): void
    {
        if (! empty($rows)) {
            MonitorUptimeDaily::upsert($rows, ['monitor_id', 'bucket_start'], self::UPDATE_COLUMNS);
        }
    }

    /**
     * @return MonitorUptimeHourly[]
     */
    public function findHourlyBetween(Carbon $from, Carbon $to): array
    {
        return MonitorUptimeHourly::query()
            ->where('bucket_start', '>=', $from)
            ->where('bucket_start', '<', $to)
            ->get()
            ->all();
    }

    /**
     * @return array{total: int, up: int}
     */
    public function aggregateHourlySince(int $monitorId, Carbon $since): array
    {
        return $this->aggregate(MonitorUptimeHourly::query(), $monitorId, $since);
    }

    /**
     * @return array{total: int, up: int}
     */
    public function aggregateDailySince(int $monitorId, Carbon $since): array
    {
        return $this->aggregate(MonitorUptimeDaily::query(), $monitorId, $since);
    }

    /**
     * @return array{total: int, up: int}
     */
    private function aggregate(Builder $query, int $monitorId, Carbon $since): array
    {
        $row = $query
            ->where('monitor_id', $monitorId)
            ->where('bucket_start', '>=', $since)
            ->selectRaw('COALESCE(SUM(checks_total), 0) AS total, COALESCE(SUM(checks_up), 0) AS up')
            ->first();

        return ['total' => (int) $row->total, 'up' => (int) $row->up];
    }
}
