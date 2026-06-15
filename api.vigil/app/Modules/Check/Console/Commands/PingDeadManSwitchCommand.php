<?php

namespace App\Modules\Check\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Emits the per-minute heartbeat to an EXTERNAL dead-man's switch so that if
 * Vigil's scheduler (or the whole box) dies, the external service pages you.
 * No-ops cleanly when VIGIL_DEADMAN_URL is unset. See PLAN.md §12.
 */
class PingDeadManSwitchCommand extends Command
{
    protected $signature = 'monitoring:deadman-ping';

    protected $description = 'Ping the external dead-man heartbeat so Vigil is watched by something off-box.';

    public function handle(): int
    {
        $url = config('vigil.deadman_url');

        if (empty($url)) {
            $this->info('Dead-man switch not configured (VIGIL_DEADMAN_URL unset); skipping.');

            return self::SUCCESS;
        }

        try {
            Http::timeout(10)->get($url);
            $this->info('Dead-man heartbeat sent.');
        } catch (Throwable $exception) {
            // Never let a failed external ping break the scheduler tick.
            $this->warn('Dead-man heartbeat failed: '.$exception->getMessage());
        }

        return self::SUCCESS;
    }
}
