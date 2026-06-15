<?php

use App\Models\User;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Monitor\Models\MonitorUptimeDaily;
use App\Modules\Monitor\Models\MonitorUptimeHourly;
use App\Modules\StatusPage\Models\StatusPage;

function statusUser(): User
{
    return User::factory()->create();
}

// Admin CRUD ----------------------------------------------------------------

it('lists status pages for an authenticated user', function () {
    StatusPage::factory()->count(2)->create();

    $this->actingAs(statusUser())->getJson('/api/status-pages')
        ->assertOk()->assertJsonCount(2, 'data');
});

it('rejects unauthenticated admin listing', function () {
    $this->getJson('/api/status-pages')->assertUnauthorized();
});

it('creates a status page and generates a slug', function () {
    $response = $this->actingAs(statusUser())->postJson('/api/status-pages', [
        'name' => 'Public Status',
        'branding' => ['headline' => 'All systems'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Public Status')
        ->assertJsonPath('data.slug', 'public-status');
});

it('rejects a duplicate custom domain', function () {
    StatusPage::factory()->create(['custom_domain' => 'status.acme.com']);

    $response = $this->actingAs(statusUser())->postJson('/api/status-pages', [
        'name' => 'Another',
        'custom_domain' => 'status.acme.com',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['custom_domain']);
});

it('updates and deletes a status page', function () {
    $page = StatusPage::factory()->create(['name' => 'Old']);

    $this->actingAs(statusUser())->patchJson("/api/status-pages/{$page->id}", ['name' => 'New'])
        ->assertOk()->assertJsonPath('data.name', 'New');

    $this->actingAs(statusUser())->deleteJson("/api/status-pages/{$page->id}")->assertOk();
    expect(StatusPage::find($page->id))->toBeNull();
});

it('attaches and detaches a monitor with grouping', function () {
    $page = StatusPage::factory()->create();
    $monitor = Monitor::factory()->create();

    $this->actingAs(statusUser())->postJson("/api/status-pages/{$page->id}/monitors/{$monitor->id}", [
        'group_name' => 'APIs',
        'sort_order' => 2,
    ])->assertOk();

    $pivot = $page->monitors()->where('monitors.id', $monitor->id)->first()->pivot;
    expect($pivot->group_name)->toBe('APIs')->and($pivot->sort_order)->toBe(2);

    $this->actingAs(statusUser())->deleteJson("/api/status-pages/{$page->id}/monitors/{$monitor->id}")->assertOk();
    expect($page->monitors()->count())->toBe(0);
});

// Public page ---------------------------------------------------------------

it('renders a public status page from rollups without auth', function () {
    $page = StatusPage::factory()->create(['slug' => 'acme', 'is_public' => true]);
    $up = Monitor::factory()->up()->create(['name' => 'API']);
    $down = Monitor::factory()->down()->create(['name' => 'Web']);
    $page->monitors()->attach($up->id, ['group_name' => 'Core', 'sort_order' => 1]);
    $page->monitors()->attach($down->id, ['group_name' => 'Core', 'sort_order' => 2]);

    MonitorUptimeHourly::insert([
        'monitor_id' => $up->id, 'bucket_start' => now()->subHour()->startOfHour(),
        'checks_total' => 60, 'checks_up' => 60, 'uptime_ratio' => 1, 'p50_ms' => 100, 'p95_ms' => 150, 'max_ms' => 200,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    MonitorUptimeDaily::insert([
        'monitor_id' => $up->id, 'bucket_start' => now()->subDay()->startOfDay(),
        'checks_total' => 1440, 'checks_up' => 1440, 'uptime_ratio' => 1, 'p50_ms' => 110, 'p95_ms' => 160, 'max_ms' => 210,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/status/acme');

    $response->assertOk()
        ->assertJsonPath('data.overallStatus', 'degraded')
        ->assertJsonPath('data.groups.0.name', 'Core')
        ->assertJsonPath('data.groups.0.monitors.0.name', 'API')
        ->assertJsonPath('data.groups.0.monitors.0.uptime.24h', 100);
});

it('returns 404 for a non-public or unknown status page', function () {
    StatusPage::factory()->create(['slug' => 'private-page', 'is_public' => false]);

    $this->getJson('/api/status/private-page')->assertNotFound();
    $this->getJson('/api/status/does-not-exist')->assertNotFound();
});
