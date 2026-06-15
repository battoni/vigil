<?php

namespace App\Modules\Check\Console\Commands;

use App\Modules\Check\Jobs\RunCheckJob;
use App\Modules\Monitor\Repositories\MonitorRepository;
use Illuminate\Console\Command;

class DispatchDueChecksCommand extends Command
{
    protected $signature = 'checks:dispatch';

    protected $description = 'Enqueue RunCheckJob for every monitor that is due.';

    /**
     * Short lease window: long enough to cover a job execution, short enough
     * that a lost job becomes due again quickly. See PLAN.md §5.
     */
    private const LEASE_SECONDS = 90;

    public function handle(MonitorRepository $monitors): int
    {
        $due = $monitors->findDueMonitors();

        foreach ($due as $monitor) {
            $monitors->leaseForCheck($monitor->id, self::LEASE_SECONDS);
            RunCheckJob::dispatch($monitor->id)->onQueue('checks');
        }

        $this->info(count($due).' check(s) dispatched.');

        return self::SUCCESS;
    }
}
