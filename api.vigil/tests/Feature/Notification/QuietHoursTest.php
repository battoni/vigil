<?php

use App\Modules\Incident\Enums\IncidentEvent;
use App\Modules\Incident\Models\Incident;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Notification\Enums\NotificationStatus;
use App\Modules\Notification\Jobs\SendDeferredAlertJob;
use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Notification\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function quietMonitor(): Monitor
{
    return Monitor::factory()->create([
        'config' => ['quiet_hours' => ['start' => '00:00', 'end' => '23:59', 'tz' => 'UTC']],
    ]);
}

it('defers a non-critical recovery alert during quiet hours instead of dropping it', function () {
    Queue::fake();
    $monitor = quietMonitor();
    attach($monitor, NotificationChannel::factory()->slack()->create());
    $incident = Incident::factory()->resolved()->create(['monitor_id' => $monitor->id]);

    $handled = dispatcher()->send($monitor, IncidentEvent::RECOVERED, $incident);

    expect($handled)->toBeTrue();
    Queue::assertPushed(SendDeferredAlertJob::class, fn (SendDeferredAlertJob $job) => $job->monitorId === $monitor->id && $job->event === IncidentEvent::RECOVERED->value);
    expect(NotificationLog::where('incident_id', $incident->id)->where('status', NotificationStatus::SENT->value)->count())->toBe(0);
});

it('sends a critical down alert immediately, bypassing quiet hours', function () {
    Queue::fake();
    Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);
    $monitor = quietMonitor();
    attach($monitor, NotificationChannel::factory()->slack()->create());
    $incident = Incident::factory()->create(['monitor_id' => $monitor->id]);

    dispatcher()->send($monitor, IncidentEvent::DOWN, $incident, 'timeout');

    Queue::assertNotPushed(SendDeferredAlertJob::class);
    expect(NotificationLog::where('incident_id', $incident->id)->where('status', NotificationStatus::SENT->value)->exists())->toBeTrue();
});

it('delivers the deferred alert when the job runs at window end', function () {
    Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);
    $monitor = quietMonitor();
    attach($monitor, NotificationChannel::factory()->slack()->create());
    $incident = Incident::factory()->resolved()->create(['monitor_id' => $monitor->id]);

    SendDeferredAlertJob::dispatchSync($monitor->id, IncidentEvent::RECOVERED->value, $incident->id, null);

    expect(NotificationLog::where('incident_id', $incident->id)->where('event', IncidentEvent::RECOVERED->value)->where('status', NotificationStatus::SENT->value)->exists())->toBeTrue();
});

it('does not defer when the monitor has no quiet hours configured', function () {
    Queue::fake();
    Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);
    $monitor = Monitor::factory()->create(['config' => []]);
    attach($monitor, NotificationChannel::factory()->slack()->create());
    $incident = Incident::factory()->resolved()->create(['monitor_id' => $monitor->id]);

    dispatcher()->send($monitor, IncidentEvent::RECOVERED, $incident);

    Queue::assertNotPushed(SendDeferredAlertJob::class);
    expect(NotificationLog::where('incident_id', $incident->id)->where('status', NotificationStatus::SENT->value)->exists())->toBeTrue();
});
