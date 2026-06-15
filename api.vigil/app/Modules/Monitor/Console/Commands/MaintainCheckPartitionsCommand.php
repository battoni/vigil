<?php

namespace App\Modules\Monitor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pre-creates next month's check_results partition by reorganizing the pmax
 * catch-all. MySQL-only; no-ops on other drivers (SQLite in tests). See
 * PLAN.md §11.
 */
class MaintainCheckPartitionsCommand extends Command
{
    protected $signature = 'checks:partition-maintenance';

    protected $description = 'Pre-create next month\'s check_results RANGE partition (MySQL only).';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->info('Partition maintenance skipped (non-MySQL driver).');

            return self::SUCCESS;
        }

        $existingMonths = $this->existingPartitionMonths();
        $nextBoundary = $this->nextBoundary($existingMonths);
        $partitionName = 'p'.$nextBoundary->format('Ym');

        if (in_array($nextBoundary->format('Ym'), $existingMonths, true)) {
            $this->info("Partition {$partitionName} already exists.");

            return self::SUCCESS;
        }

        DB::statement(
            'ALTER TABLE check_results REORGANIZE PARTITION pmax INTO ('.
            "PARTITION {$partitionName} VALUES LESS THAN (TO_DAYS('{$nextBoundary->toDateString()}')), ".
            'PARTITION pmax VALUES LESS THAN (MAXVALUE))'
        );

        $this->info("Created partition {$partitionName}.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, string> e.g. ['202606', '202607', ...]
     */
    private function existingPartitionMonths(): array
    {
        $partitions = DB::select(
            'SELECT PARTITION_NAME AS name FROM INFORMATION_SCHEMA.PARTITIONS '.
            "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'check_results' AND PARTITION_NAME IS NOT NULL"
        );

        return collect($partitions)
            ->pluck('name')
            ->filter(fn ($name) => preg_match('/^p(\d{6})$/', (string) $name))
            ->map(fn ($name) => substr((string) $name, 1))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $existingMonths
     */
    private function nextBoundary(array $existingMonths): Carbon
    {
        if (empty($existingMonths)) {
            return now()->startOfMonth()->addMonths(2);
        }

        return Carbon::createFromFormat('Ym', end($existingMonths))->startOfMonth()->addMonth();
    }
}
