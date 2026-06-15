<?php

namespace App\Modules\StatusPage\Services;

use App\Modules\Monitor\Repositories\RollupRepository;

/**
 * Computes uptime percentages over fixed ranges. Reads ONLY the rollup tables
 * (hourly for 24h, daily for longer ranges) — never raw check_results — so the
 * query stays flat no matter how much history accrues. See PLAN.md §8/§11.
 */
class UptimeQueryService
{
    public function __construct(
        private RollupRepository $rollupRepository,
    ) {}

    /**
     * @return array<string, float|null> e.g. ['24h' => 99.95, '7d' => ...]
     */
    public function uptimeForMonitor(int $monitorId): array
    {
        return [
            '24h' => $this->ratio($this->rollupRepository->aggregateHourlySince($monitorId, now()->subDay())),
            '7d' => $this->ratio($this->rollupRepository->aggregateDailySince($monitorId, now()->subDays(7))),
            '30d' => $this->ratio($this->rollupRepository->aggregateDailySince($monitorId, now()->subDays(30))),
            '90d' => $this->ratio($this->rollupRepository->aggregateDailySince($monitorId, now()->subDays(90))),
        ];
    }

    /**
     * @param  array{total: int, up: int}  $aggregate
     */
    private function ratio(array $aggregate): ?float
    {
        if ($aggregate['total'] === 0) {
            return null;
        }

        return round(($aggregate['up'] / $aggregate['total']) * 100, 2);
    }
}
