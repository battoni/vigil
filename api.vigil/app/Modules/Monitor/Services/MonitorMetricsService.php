<?php

namespace App\Modules\Monitor\Services;

use App\Modules\Monitor\Models\CheckResult;
use App\Modules\Monitor\Repositories\CheckResultRepository;
use App\Modules\Monitor\Repositories\RollupRepository;
use Illuminate\Support\Carbon;

/**
 * Read-only metrics for the dashboard monitor detail view. Uptime % and the
 * latency/uptime series come from rollups; recent checks come from raw
 * check_results (the only place raw is read — last-N detail). See APP_PLAN §3.
 */
class MonitorMetricsService
{
    private const RANGES = ['24h', '7d', '30d', '90d'];

    public function __construct(
        private UptimeQueryService $uptimeQueryService,
        private RollupRepository $rollupRepository,
        private CheckResultRepository $checkResultRepository,
    ) {}

    /**
     * @return array<string, float|null>
     */
    public function uptime(int $monitorId): array
    {
        return $this->uptimeQueryService->uptimeForMonitor($monitorId);
    }

    /**
     * @return CheckResult[]
     */
    public function recentChecks(int $monitorId, ?Carbon $since = null): array
    {
        return $this->checkResultRepository->findRecentForMonitor($monitorId, $since ?? now()->subDay());
    }

    /**
     * Time-series for charts: hourly buckets for 24h, daily for longer ranges.
     *
     * @return array<int, array<string, mixed>>
     */
    public function series(int $monitorId, string $range): array
    {
        $range = in_array($range, self::RANGES, true) ? $range : '7d';

        $buckets = $range === '24h'
            ? $this->rollupRepository->hourlySeriesForMonitor($monitorId, now()->subDay())
            : $this->rollupRepository->dailySeriesForMonitor($monitorId, now()->subDays($this->days($range)));

        return array_map(fn ($bucket) => [
            'bucketStart' => $bucket->bucket_start?->toIso8601String(),
            'uptimeRatio' => (float) $bucket->uptime_ratio,
            'checksTotal' => $bucket->checks_total,
            'p50Ms' => $bucket->p50_ms,
            'p95Ms' => $bucket->p95_ms,
            'maxMs' => $bucket->max_ms,
        ], $buckets);
    }

    private function days(string $range): int
    {
        return match ($range) {
            '90d' => 90,
            '30d' => 30,
            default => 7,
        };
    }
}
