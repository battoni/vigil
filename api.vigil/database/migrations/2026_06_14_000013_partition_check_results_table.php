<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MySQL-only: convert check_results to a RANGE-partitioned table (monthly,
     * by TO_DAYS(checked_at)). The partition column must be part of the primary
     * key, so the PK is widened to (id, checked_at). No-ops on other drivers
     * (e.g. SQLite in the test suite). See PLAN.md §11.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE check_results DROP PRIMARY KEY, ADD PRIMARY KEY (id, checked_at)');
        DB::statement('ALTER TABLE check_results PARTITION BY RANGE (TO_DAYS(checked_at)) ('.$this->partitionDefinitions().')');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE check_results REMOVE PARTITIONING');
        DB::statement('ALTER TABLE check_results DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
    }

    /**
     * Build monthly partitions spanning the current month through +15 months,
     * plus a MAXVALUE catch-all that the monthly maintenance command reorganizes.
     */
    private function partitionDefinitions(): string
    {
        $definitions = [];
        $boundary = now()->startOfMonth();

        for ($month = 0; $month <= 15; $month++) {
            $name = 'p'.$boundary->format('Ym');
            $definitions[] = "PARTITION {$name} VALUES LESS THAN (TO_DAYS('{$boundary->toDateString()}'))";
            $boundary = $boundary->copy()->addMonth();
        }

        $definitions[] = 'PARTITION pmax VALUES LESS THAN (MAXVALUE)';

        return implode(', ', $definitions);
    }
};
