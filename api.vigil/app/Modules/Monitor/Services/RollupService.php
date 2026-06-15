<?php

namespace App\Modules\Monitor\Services;

use App\Modules\Monitor\Enums\CheckOutcome;
use App\Modules\Monitor\Models\CheckResult;
use App\Modules\Monitor\Models\MonitorUptimeHourly;
use App\Modules\Monitor\Repositories\CheckResultRepository;
use App\Modules\Monitor\Repositories\RollupRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates raw check_results into hourly buckets and hourly buckets into daily
 * buckets. Status pages and long-range graphs read these rollups, never raw.
 * Percentiles are computed in PHP so the aggregation is portable (MySQL +
 * SQLite tests). See PLAN.md §11.
 */
class RollupService
{
    public function __construct(
        private CheckResultRepository $checkResultRepository,
        private RollupRepository $rollupRepository,
    ) {}

    public function rollupHour(Carbon $hourStart): void
    {
        $hourStart = $hourStart->copy()->startOfHour();
        $results = collect($this->checkResultRepository->findBetween($hourStart, $hourStart->copy()->addHour()));

        $rows = $results
            ->groupBy('monitor_id')
            ->map(fn (Collection $checks, int $monitorId) => $this->hourlyRow($monitorId, $hourStart, $checks))
            ->values()
            ->all();

        $this->rollupRepository->upsertHourly($rows);
    }

    public function rollupDay(Carbon $dayStart): void
    {
        $dayStart = $dayStart->copy()->startOfDay();
        $buckets = collect($this->rollupRepository->findHourlyBetween($dayStart, $dayStart->copy()->addDay()));

        $rows = $buckets
            ->groupBy('monitor_id')
            ->map(fn (Collection $hourly, int $monitorId) => $this->dailyRow($monitorId, $dayStart, $hourly))
            ->values()
            ->all();

        $this->rollupRepository->upsertDaily($rows);
    }

    /**
     * @param  Collection<int, CheckResult>  $checks
     * @return array<string, mixed>
     */
    private function hourlyRow(int $monitorId, Carbon $bucketStart, Collection $checks): array
    {
        $total = $checks->count();
        $up = $checks->where('result', CheckOutcome::UP)->count();
        $responseTimes = $checks->pluck('response_time_ms')->filter(fn ($value) => $value !== null)->map(fn ($value) => (int) $value)->all();

        return $this->bucketRow($monitorId, $bucketStart, $total, $up, $responseTimes);
    }

    /**
     * @param  Collection<int, MonitorUptimeHourly>  $hourly
     * @return array<string, mixed>
     */
    private function dailyRow(int $monitorId, Carbon $bucketStart, Collection $hourly): array
    {
        $total = (int) $hourly->sum('checks_total');
        $up = (int) $hourly->sum('checks_up');

        return [
            'monitor_id' => $monitorId,
            'bucket_start' => $bucketStart->toDateTimeString(),
            'checks_total' => $total,
            'checks_up' => $up,
            'uptime_ratio' => $total > 0 ? round($up / $total, 4) : 0,
            // Daily percentiles are approximate: a checks-weighted mean of the
            // hourly percentiles (raw rows are not re-read for the day).
            'p50_ms' => $this->weightedAverage($hourly, 'p50_ms'),
            'p95_ms' => $this->weightedAverage($hourly, 'p95_ms'),
            'max_ms' => $hourly->max('max_ms'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * @param  array<int, int>  $responseTimes
     * @return array<string, mixed>
     */
    private function bucketRow(int $monitorId, Carbon $bucketStart, int $total, int $up, array $responseTimes): array
    {
        return [
            'monitor_id' => $monitorId,
            'bucket_start' => $bucketStart->toDateTimeString(),
            'checks_total' => $total,
            'checks_up' => $up,
            'uptime_ratio' => $total > 0 ? round($up / $total, 4) : 0,
            'p50_ms' => $this->percentile($responseTimes, 50),
            'p95_ms' => $this->percentile($responseTimes, 95),
            'max_ms' => empty($responseTimes) ? null : max($responseTimes),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Nearest-rank percentile.
     *
     * @param  array<int, int>  $values
     */
    private function percentile(array $values, int $percentile): ?int
    {
        if (empty($values)) {
            return null;
        }

        sort($values);
        $rank = (int) ceil(($percentile / 100) * count($values));
        $index = max(0, min($rank - 1, count($values) - 1));

        return $values[$index];
    }

    /**
     * @param  Collection<int, MonitorUptimeHourly>  $hourly
     */
    private function weightedAverage(Collection $hourly, string $column): ?int
    {
        $weightedSum = 0;
        $weight = 0;

        foreach ($hourly as $bucket) {
            if ($bucket->{$column} === null) {
                continue;
            }

            $weightedSum += $bucket->{$column} * $bucket->checks_total;
            $weight += $bucket->checks_total;
        }

        return $weight > 0 ? (int) round($weightedSum / $weight) : null;
    }
}
