<?php

use App\Modules\Incident\Models\Incident;
use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Enums\MonitorType;
use App\Modules\Monitor\Models\CheckResult;
use App\Modules\Monitor\Models\MaintenanceWindow;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Notification\Models\NotificationLog;
use App\Modules\Project\Models\Project;
use App\Modules\StatusPage\Models\StatusPage;

it('creates a project with a generated slug', function () {
    $project = Project::factory()->create(['name' => 'Acme Corp', 'slug' => null]);

    expect($project->slug)->not->toBeNull();
});

it('creates a monitor with enum casts and json config', function () {
    $monitor = Monitor::factory()->create();

    expect($monitor->type)->toBeInstanceOf(MonitorType::class)
        ->and($monitor->status)->toBe(MonitorStatus::PENDING)
        ->and($monitor->config)->toBeArray()
        ->and($monitor->project)->toBeInstanceOf(Project::class);
});

it('records check results linked to a monitor without a foreign key', function () {
    $monitor = Monitor::factory()->create();
    CheckResult::factory()->count(3)->create(['monitor_id' => $monitor->id]);

    expect($monitor->checkResults)->toHaveCount(3)
        ->and($monitor->checkResults->first()->monitor->id)->toBe($monitor->id);
});

it('links monitors to notification channels via the channel_monitor pivot', function () {
    $monitor = Monitor::factory()->create();
    $channel = NotificationChannel::factory()->slack()->create();

    $monitor->channels()->attach($channel, ['notify_on_recovery' => true]);

    expect($monitor->channels)->toHaveCount(1)
        ->and($channel->fresh()->monitors)->toHaveCount(1);
});

it('links monitors to status pages via the monitor_status_page pivot', function () {
    $monitor = Monitor::factory()->create();
    $statusPage = StatusPage::factory()->create();

    $statusPage->monitors()->attach($monitor, ['group_name' => 'API', 'sort_order' => 1]);

    expect($statusPage->monitors)->toHaveCount(1);
});

it('opens an incident with notification logs', function () {
    $incident = Incident::factory()->create();
    NotificationLog::factory()->create(['incident_id' => $incident->id]);

    expect($incident->monitor)->toBeInstanceOf(Monitor::class)
        ->and($incident->notificationLogs)->toHaveCount(1);
});

it('creates a heartbeat monitor with a token and no next check', function () {
    $monitor = Monitor::factory()->heartbeat()->create();

    expect($monitor->type)->toBe(MonitorType::HEARTBEAT)
        ->and($monitor->heartbeat_token)->not->toBeNull()
        ->and($monitor->next_check_at)->toBeNull();
});

it('creates maintenance windows including global ones', function () {
    $scoped = MaintenanceWindow::factory()->create();
    $global = MaintenanceWindow::factory()->global()->create();

    expect($scoped->monitor)->toBeInstanceOf(Monitor::class)
        ->and($global->monitor_id)->toBeNull();
});
