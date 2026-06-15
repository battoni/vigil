<?php

use App\Models\User;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Notification\Models\NotificationChannel;

function channelUser(): User
{
    return User::factory()->create();
}

it('lists channels for an authenticated user', function () {
    NotificationChannel::factory()->count(2)->create();

    $this->actingAs(channelUser())->getJson('/api/channels')
        ->assertOk()->assertJsonCount(2, 'data');
});

it('rejects unauthenticated channel listing', function () {
    $this->getJson('/api/channels')->assertUnauthorized();
});

it('creates a slack channel', function () {
    $response = $this->actingAs(channelUser())->postJson('/api/channels', [
        'name' => 'Ops Slack',
        'type' => 'slack',
        'config' => ['webhook_url' => 'https://hooks.slack.com/services/T/B/X'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Ops Slack')
        ->assertJsonPath('data.type', 'slack');
});

it('rejects an unknown channel type', function () {
    $response = $this->actingAs(channelUser())->postJson('/api/channels', [
        'name' => 'Bad',
        'type' => 'carrier-pigeon',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['type']);
});

it('masks secret config values in the response', function () {
    $channel = NotificationChannel::factory()->create([
        'type' => 'whatsapp',
        'config' => ['base_url' => 'http://evolution:8080', 'api_key' => 'super-secret', 'instance' => 'vigil', 'recipients' => ['+1555']],
    ]);

    $response = $this->actingAs(channelUser())->getJson("/api/channels/{$channel->id}");

    $response->assertOk()
        ->assertJsonPath('data.config.api_key', '••••••')
        ->assertJsonPath('data.config.instance', 'vigil');
});

it('updates a channel', function () {
    $channel = NotificationChannel::factory()->create(['name' => 'Old', 'is_active' => true]);

    $response = $this->actingAs(channelUser())->patchJson("/api/channels/{$channel->id}", [
        'name' => 'New',
        'is_active' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New')
        ->assertJsonPath('data.isActive', false);
});

it('deletes a channel and returns the deleted resource', function () {
    $channel = NotificationChannel::factory()->create();

    $response = $this->actingAs(channelUser())->deleteJson("/api/channels/{$channel->id}");

    $response->assertOk()->assertJsonPath('data.id', (string) $channel->id);
    expect(NotificationChannel::find($channel->id))->toBeNull();
});

// Routing -------------------------------------------------------------------

it('attaches a channel to a monitor with routing settings', function () {
    $channel = NotificationChannel::factory()->slack()->create();
    $monitor = Monitor::factory()->create();

    $response = $this->actingAs(channelUser())->postJson("/api/channels/{$channel->id}/monitors/{$monitor->id}", [
        'min_severity' => 'critical',
        'notify_on_recovery' => false,
    ]);

    $response->assertOk();
    $pivot = $channel->monitors()->where('monitors.id', $monitor->id)->first()->pivot;
    expect($pivot->min_severity)->toBe('critical')
        ->and((bool) $pivot->notify_on_recovery)->toBeFalse();
});

it('is idempotent when attaching the same channel-monitor pair', function () {
    $channel = NotificationChannel::factory()->slack()->create();
    $monitor = Monitor::factory()->create();

    $this->actingAs(channelUser())->postJson("/api/channels/{$channel->id}/monitors/{$monitor->id}", ['notify_on_recovery' => true]);
    $this->actingAs(channelUser())->postJson("/api/channels/{$channel->id}/monitors/{$monitor->id}", ['notify_on_recovery' => true]);

    expect($channel->monitors()->count())->toBe(1);
});

it('detaches a channel from a monitor', function () {
    $channel = NotificationChannel::factory()->slack()->create();
    $monitor = Monitor::factory()->create();
    $channel->monitors()->attach($monitor->id, ['notify_on_recovery' => true]);

    $response = $this->actingAs(channelUser())->deleteJson("/api/channels/{$channel->id}/monitors/{$monitor->id}");

    $response->assertOk();
    expect($channel->monitors()->count())->toBe(0);
});
