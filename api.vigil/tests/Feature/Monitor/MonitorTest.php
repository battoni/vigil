<?php

use App\Models\User;
use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Project\Models\Project;

function actingUser(): User
{
    return User::factory()->create();
}

it('lists monitors and filters by project', function () {
    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();
    Monitor::factory()->count(2)->create(['project_id' => $projectA->id]);
    Monitor::factory()->create(['project_id' => $projectB->id]);

    $response = $this->actingAs(actingUser())->getJson("/api/monitors?project_id={$projectA->id}");

    $response->assertOk()->assertJsonCount(2, 'data');
});

it('rejects unauthenticated listing', function () {
    $this->getJson('/api/monitors')->assertUnauthorized();
});

it('shows a single monitor', function () {
    $monitor = Monitor::factory()->create(['name' => 'API health']);

    $response = $this->actingAs(actingUser())->getJson("/api/monitors/{$monitor->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', (string) $monitor->id)
        ->assertJsonPath('data.name', 'API health');
});

it('returns 404 for an unknown monitor', function () {
    $this->actingAs(actingUser())->getJson('/api/monitors/999999')->assertNotFound();
});

it('exposes attached channel ids on show', function () {
    $monitor = Monitor::factory()->create();
    $channel = NotificationChannel::factory()->slack()->create();
    $monitor->channels()->attach($channel->id, ['notify_on_recovery' => true]);

    $response = $this->actingAs(actingUser())->getJson("/api/monitors/{$monitor->id}");

    $response->assertOk()->assertJsonPath('data.channelIds', [(string) $channel->id]);
});

it('creates an http monitor and seeds next_check_at and pending status', function () {
    $project = Project::factory()->create();

    $response = $this->actingAs(actingUser())->postJson('/api/monitors', [
        'project_id' => $project->id,
        'name' => 'Homepage',
        'type' => 'http',
        'target' => 'https://example.com',
        'interval_seconds' => 60,
        'config' => ['expected_status' => 200],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', MonitorStatus::PENDING->value)
        ->assertJsonPath('data.type', 'http');

    expect($response->json('data.nextCheckAt'))->not->toBeNull();
});

it('generates a heartbeat token and url with no next check for heartbeat monitors', function () {
    $project = Project::factory()->create();

    $response = $this->actingAs(actingUser())->postJson('/api/monitors', [
        'project_id' => $project->id,
        'name' => 'Nightly cron',
        'type' => 'heartbeat',
        'target' => 'cron',
        'interval_seconds' => 60,
        'config' => ['grace_period' => 300],
    ]);

    $response->assertCreated();
    expect($response->json('data.heartbeatToken'))->not->toBeNull()
        ->and($response->json('data.heartbeatUrl'))->toContain('/api/heartbeats/')
        ->and($response->json('data.nextCheckAt'))->toBeNull();
});

it('enforces the 60-second minimum interval', function () {
    $project = Project::factory()->create();

    $response = $this->actingAs(actingUser())->postJson('/api/monitors', [
        'project_id' => $project->id,
        'name' => 'Too fast',
        'type' => 'http',
        'target' => 'https://example.com',
        'interval_seconds' => 30,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['interval_seconds']);
});

it('rejects an unknown monitor type', function () {
    $project = Project::factory()->create();

    $response = $this->actingAs(actingUser())->postJson('/api/monitors', [
        'project_id' => $project->id,
        'name' => 'Bad type',
        'type' => 'carrier-pigeon',
        'target' => 'sky',
        'interval_seconds' => 60,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['type']);
});

it('requires an existing project', function () {
    $response = $this->actingAs(actingUser())->postJson('/api/monitors', [
        'project_id' => 999999,
        'name' => 'Orphan',
        'type' => 'http',
        'target' => 'https://example.com',
        'interval_seconds' => 60,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['project_id']);
});

it('rejects an http monitor targeting a blocked address at create-time', function (string $target) {
    $project = Project::factory()->create();

    $response = $this->actingAs(actingUser())->postJson('/api/monitors', [
        'project_id' => $project->id,
        'name' => 'SSRF attempt',
        'type' => 'http',
        'target' => $target,
        'interval_seconds' => 60,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['target']);
})->with([
    'http://127.0.0.1',
    'http://169.254.169.254/latest/meta-data',
    'http://localhost:8080',
    'https://10.0.0.5',
]);

it('allows a heartbeat monitor with a non-network target', function () {
    $project = Project::factory()->create();

    $response = $this->actingAs(actingUser())->postJson('/api/monitors', [
        'project_id' => $project->id,
        'name' => 'Cron heartbeat',
        'type' => 'heartbeat',
        'target' => 'localhost-cron',
        'interval_seconds' => 60,
    ]);

    $response->assertCreated();
});

it('pauses a monitor and clears its next check', function () {
    $monitor = Monitor::factory()->up()->create();

    $response = $this->actingAs(actingUser())->patchJson("/api/monitors/{$monitor->id}", [
        'status' => MonitorStatus::PAUSED->value,
    ]);

    $response->assertOk()->assertJsonPath('data.status', MonitorStatus::PAUSED->value);
    expect($response->json('data.nextCheckAt'))->toBeNull();
});

it('resumes a paused monitor and makes it due again', function () {
    $monitor = Monitor::factory()->paused()->create();

    $response = $this->actingAs(actingUser())->patchJson("/api/monitors/{$monitor->id}", [
        'status' => MonitorStatus::PENDING->value,
    ]);

    $response->assertOk();
    expect($response->json('data.nextCheckAt'))->not->toBeNull();
});

it('updates monitor configuration', function () {
    $monitor = Monitor::factory()->create(['interval_seconds' => 60]);

    $response = $this->actingAs(actingUser())->patchJson("/api/monitors/{$monitor->id}", [
        'interval_seconds' => 120,
        'config' => ['expected_status' => 201],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.intervalSeconds', 120)
        ->assertJsonPath('data.config.expected_status', 201);
});

it('deletes a monitor and returns the deleted resource', function () {
    $monitor = Monitor::factory()->create();

    $response = $this->actingAs(actingUser())->deleteJson("/api/monitors/{$monitor->id}");

    $response->assertOk()->assertJsonPath('data.id', (string) $monitor->id);
    expect(Monitor::find($monitor->id))->toBeNull();
});
