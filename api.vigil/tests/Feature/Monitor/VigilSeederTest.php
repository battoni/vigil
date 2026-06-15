<?php

use App\Modules\Monitor\Enums\MonitorType;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Project\Models\Project;
use Database\Seeders\VigilSeeder;

it('seeds the system project and evolution-health monitor routing around whatsapp', function () {
    $this->seed(VigilSeeder::class);

    $project = Project::where('slug', 'system')->first();
    expect($project)->not->toBeNull();

    $monitor = Monitor::where('name', 'Evolution WhatsApp Session')->first();
    expect($monitor)->not->toBeNull()
        ->and($monitor->type)->toBe(MonitorType::HEARTBEAT)
        ->and($monitor->config['exclude_channels'])->toBe(['whatsapp'])
        ->and($monitor->heartbeat_token)->not->toBeNull();
});

it('is idempotent — re-seeding does not duplicate the evolution-health monitor', function () {
    $this->seed(VigilSeeder::class);
    $this->seed(VigilSeeder::class);

    expect(Monitor::where('name', 'Evolution WhatsApp Session')->count())->toBe(1)
        ->and(Project::where('slug', 'system')->count())->toBe(1);
});
