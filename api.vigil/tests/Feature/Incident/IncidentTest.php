<?php

use App\Models\User;
use App\Modules\Check\Jobs\RunCheckJob;
use App\Modules\Check\Support\SsrfGuard;
use App\Modules\Incident\Models\Incident;
use App\Modules\Monitor\Models\Monitor;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->app->instance(SsrfGuard::class, new SsrfGuard(fn () => ['203.0.113.10']));
});

function incidentUser(): User
{
    return User::factory()->create();
}

// Engine integration (listeners run for real) -------------------------------

it('opens an incident when a monitor is confirmed down', function () {
    Http::fake(['*' => Http::response('', 500)]);
    $monitor = Monitor::factory()->up()->create(['confirmation_threshold' => 1]);

    RunCheckJob::dispatchSync($monitor->id);

    $incident = Incident::where('monitor_id', $monitor->id)->first();
    expect($incident)->not->toBeNull()
        ->and($incident->resolved_at)->toBeNull()
        ->and($incident->cause)->toContain('500');
});

it('does not open a second incident while one is already open', function () {
    Http::fake(['*' => Http::response('', 500)]);
    $monitor = Monitor::factory()->up()->create(['confirmation_threshold' => 1]);

    RunCheckJob::dispatchSync($monitor->id);
    RunCheckJob::dispatchSync($monitor->id);

    expect(Incident::where('monitor_id', $monitor->id)->count())->toBe(1);
});

it('resolves the open incident when the monitor recovers', function () {
    Http::fake(['*' => Http::sequence()->push('', 500)->push('ok', 200)]);
    $monitor = Monitor::factory()->up()->create(['confirmation_threshold' => 1, 'recovery_threshold' => 1]);

    RunCheckJob::dispatchSync($monitor->id);
    RunCheckJob::dispatchSync($monitor->id);

    $incident = Incident::where('monitor_id', $monitor->id)->first();
    expect($incident->resolved_at)->not->toBeNull()
        ->and($incident->duration_seconds)->not->toBeNull();
});

// API surface ---------------------------------------------------------------

it('lists incidents and filters by monitor and open state', function () {
    $monitor = Monitor::factory()->create();
    Incident::factory()->count(2)->create(['monitor_id' => $monitor->id]);
    Incident::factory()->resolved()->create(['monitor_id' => $monitor->id]);
    Incident::factory()->create();

    $all = $this->actingAs(incidentUser())->getJson("/api/incidents?monitor_id={$monitor->id}");
    $all->assertOk()->assertJsonCount(3, 'data');

    $open = $this->actingAs(incidentUser())->getJson("/api/incidents?monitor_id={$monitor->id}&open=1");
    $open->assertOk()->assertJsonCount(2, 'data');
});

it('rejects unauthenticated incident listing', function () {
    $this->getJson('/api/incidents')->assertUnauthorized();
});

it('shows a single incident with its monitor name', function () {
    $monitor = Monitor::factory()->create(['name' => 'Checkout API']);
    $incident = Incident::factory()->create(['monitor_id' => $monitor->id]);

    $response = $this->actingAs(incidentUser())->getJson("/api/incidents/{$incident->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', (string) $incident->id)
        ->assertJsonPath('data.monitorName', 'Checkout API')
        ->assertJsonPath('data.isOpen', true);
});

it('returns 404 for an unknown incident', function () {
    $this->actingAs(incidentUser())->getJson('/api/incidents/999999')->assertNotFound();
});

it('acknowledges an incident', function () {
    $user = incidentUser();
    $incident = Incident::factory()->create();

    $response = $this->actingAs($user)->patchJson("/api/incidents/{$incident->id}/acknowledge");

    $response->assertOk()
        ->assertJsonPath('data.acknowledgedBy', (string) $user->id);
    expect($incident->fresh()->acknowledged_at)->not->toBeNull();
});

it('rejects unauthenticated acknowledgement', function () {
    $incident = Incident::factory()->create();

    $this->patchJson("/api/incidents/{$incident->id}/acknowledge")->assertUnauthorized();
});
