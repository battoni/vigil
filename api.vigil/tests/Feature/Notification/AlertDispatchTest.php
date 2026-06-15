<?php

use App\Modules\Check\Jobs\RunCheckJob;
use App\Modules\Check\Support\SsrfGuard;
use App\Modules\Incident\Enums\IncidentEvent;
use App\Modules\Incident\Models\Incident;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Notification\Channels\EmailChannel;
use App\Modules\Notification\Enums\ChannelType;
use App\Modules\Notification\Enums\NotificationStatus;
use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Notification\Models\NotificationLog;
use App\Modules\Notification\Services\AlertDispatchService;
use App\Modules\Notification\Support\AlertMessage;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->app->instance(SsrfGuard::class, new SsrfGuard(fn () => ['203.0.113.10']));
});

function whatsappChannel(): NotificationChannel
{
    return NotificationChannel::factory()->create([
        'type' => ChannelType::WHATSAPP,
        'config' => ['base_url' => 'http://evolution:8080', 'instance' => 'vigil', 'api_key' => 'k', 'recipients' => ['+15550001111']],
    ]);
}

function attach(Monitor $monitor, NotificationChannel $channel, array $pivot = []): void
{
    $monitor->channels()->attach($channel->id, array_merge(['notify_on_recovery' => true], $pivot));
}

function dispatcher(): AlertDispatchService
{
    return app(AlertDispatchService::class);
}

it('falls through from a failing whatsapp channel to slack', function () {
    Http::fake([
        'evolution:8080/*' => Http::response('', 500),
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);
    $monitor = Monitor::factory()->create();
    attach($monitor, whatsappChannel());
    $slack = NotificationChannel::factory()->slack()->create();
    attach($monitor, $slack);
    $incident = Incident::factory()->create(['monitor_id' => $monitor->id]);

    $delivered = dispatcher()->send($monitor, IncidentEvent::DOWN, $incident, 'timeout');

    expect($delivered)->toBeTrue();
    expect(NotificationLog::where('incident_id', $incident->id)->where('status', NotificationStatus::FAILED->value)->count())->toBe(1);
    expect(NotificationLog::where('incident_id', $incident->id)->where('status', NotificationStatus::SENT->value)->where('channel_id', $slack->id)->count())->toBe(1);
});

it('does not re-alert for an event already delivered (dedup)', function () {
    Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);
    $monitor = Monitor::factory()->create();
    attach($monitor, NotificationChannel::factory()->slack()->create());
    $incident = Incident::factory()->create(['monitor_id' => $monitor->id]);

    dispatcher()->send($monitor, IncidentEvent::DOWN, $incident);
    dispatcher()->send($monitor, IncidentEvent::DOWN, $incident);

    expect(NotificationLog::where('incident_id', $incident->id)->where('status', NotificationStatus::SENT->value)->count())->toBe(1);
});

it('routes around an excluded channel type', function () {
    Http::fake([
        'evolution:8080/*' => Http::response('ok', 200),
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);
    $monitor = Monitor::factory()->create(['config' => ['exclude_channels' => ['whatsapp']]]);
    attach($monitor, whatsappChannel());
    $slack = NotificationChannel::factory()->slack()->create();
    attach($monitor, $slack);
    $incident = Incident::factory()->create(['monitor_id' => $monitor->id]);

    dispatcher()->send($monitor, IncidentEvent::DOWN, $incident);

    Http::assertSentCount(1); // whatsapp skipped, only slack hit
    expect(NotificationLog::where('channel_id', $slack->id)->where('status', NotificationStatus::SENT->value)->exists())->toBeTrue();
});

it('skips recovery alerts for channels with notify_on_recovery off', function () {
    Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);
    $monitor = Monitor::factory()->create();
    attach($monitor, NotificationChannel::factory()->slack()->create(), ['notify_on_recovery' => false]);
    $incident = Incident::factory()->resolved()->create(['monitor_id' => $monitor->id]);

    $delivered = dispatcher()->send($monitor, IncidentEvent::RECOVERED, $incident);

    expect($delivered)->toBeFalse();
    Http::assertNothingSent();
});

it('logs all_channels_failed when every channel fails', function () {
    Http::fake(['hooks.slack.com/*' => Http::response('', 500)]);
    $monitor = Monitor::factory()->create();
    attach($monitor, NotificationChannel::factory()->slack()->create());
    $incident = Incident::factory()->create(['monitor_id' => $monitor->id]);

    $delivered = dispatcher()->send($monitor, IncidentEvent::DOWN, $incident);

    expect($delivered)->toBeFalse();
    expect(NotificationLog::where('incident_id', $incident->id)->where('error', 'all_channels_failed')->exists())->toBeTrue();
});

// Email sub-chain -----------------------------------------------------------

it('sends email via resend when configured', function () {
    Http::fake(['api.resend.com/*' => Http::response(['id' => 'abc'], 200)]);
    $channel = NotificationChannel::factory()->create([
        'type' => ChannelType::EMAIL,
        'config' => ['recipients' => ['ops@example.com'], 'resend_api_key' => 'rk', 'from' => 'alerts@vigil.test'],
    ]);
    $monitor = Monitor::factory()->create(['name' => 'API']);

    (new EmailChannel)->notify($channel, new AlertMessage($monitor, IncidentEvent::DOWN, 'timeout'));

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.resend.com'));
});

it('falls back to smtp when resend fails', function () {
    Http::fake(['api.resend.com/*' => Http::response('', 500)]);
    $smtpUsed = false;
    $channel = NotificationChannel::factory()->create([
        'type' => ChannelType::EMAIL,
        'config' => ['recipients' => ['ops@example.com'], 'resend_api_key' => 'rk', 'from' => 'alerts@vigil.test'],
    ]);
    $monitor = Monitor::factory()->create();

    $emailChannel = new class($smtpUsed) extends EmailChannel
    {
        public function __construct(public bool &$smtpUsed) {}

        protected function sendViaSmtp(array $recipients, AlertMessage $message): void
        {
            $this->smtpUsed = true;
        }
    };

    $emailChannel->notify($channel, new AlertMessage($monitor, IncidentEvent::DOWN));

    expect($smtpUsed)->toBeTrue();
});

// End-to-end through the listeners ------------------------------------------

it('sends an alert and logs it when a monitor goes down through the engine', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
        '*' => Http::response('', 500),
    ]);
    $monitor = Monitor::factory()->up()->create(['confirmation_threshold' => 1]);
    attach($monitor, NotificationChannel::factory()->slack()->create());

    RunCheckJob::dispatchSync($monitor->id);

    $incident = Incident::where('monitor_id', $monitor->id)->first();
    expect($incident)->not->toBeNull();
    expect(NotificationLog::where('incident_id', $incident->id)->where('status', NotificationStatus::SENT->value)->exists())->toBeTrue();
});
