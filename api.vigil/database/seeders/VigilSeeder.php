<?php

namespace Database\Seeders;

use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Enums\MonitorType;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Project\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VigilSeeder extends Seeder
{
    /**
     * Seeds Vigil's internal monitoring scaffolding: a system project and the
     * Evolution (WhatsApp) session-health monitor. That monitor routes around
     * WhatsApp (exclude_channels) so a dead WhatsApp session can still page you
     * via Slack/Email. In production a small sidecar polls Evolution's
     * connectionState and pings this heartbeat; modelled as a heartbeat so it
     * does not trip the SsrfGuard against the internal Evolution host. See
     * PLAN.md §7/§12.
     */
    public function run(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'system'],
            ['name' => 'System']
        );

        Monitor::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Evolution WhatsApp Session'],
            [
                'type' => MonitorType::HEARTBEAT->value,
                'target' => 'evolution-session',
                'config' => [
                    'grace_period' => 600,
                    'exclude_channels' => ['whatsapp'],
                ],
                'interval_seconds' => 60,
                'status' => MonitorStatus::PENDING->value,
                'heartbeat_token' => Str::random(40),
            ]
        );
    }
}
