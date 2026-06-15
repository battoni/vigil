<?php

use App\Models\User;
use App\Modules\Project\Models\Project;

it('lists projects for an authenticated user', function () {
    Project::factory()->count(2)->create();

    $response = $this->actingAs(User::factory()->create())->getJson('/api/projects');

    $response->assertOk()->assertJsonCount(2, 'data');
});

it('rejects unauthenticated listing', function () {
    $response = $this->getJson('/api/projects');

    $response->assertUnauthorized();
});

it('shows a single project', function () {
    $project = Project::factory()->create(['name' => 'Acme']);

    $response = $this->actingAs(User::factory()->create())->getJson("/api/projects/{$project->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', (string) $project->id)
        ->assertJsonPath('data.name', 'Acme');
});

it('returns 404 for an unknown project', function () {
    $response = $this->actingAs(User::factory()->create())->getJson('/api/projects/999999');

    $response->assertNotFound();
});

it('creates a project and generates a slug', function () {
    $response = $this->actingAs(User::factory()->create())->postJson('/api/projects', [
        'name' => 'My New Project',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'My New Project')
        ->assertJsonPath('data.slug', 'my-new-project');
});

it('generates a unique slug when names collide', function () {
    Project::factory()->create(['name' => 'Duplicate', 'slug' => 'duplicate']);

    $response = $this->actingAs(User::factory()->create())->postJson('/api/projects', [
        'name' => 'Duplicate',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.slug', 'duplicate-1');
});

it('validates required name on create', function () {
    $response = $this->actingAs(User::factory()->create())->postJson('/api/projects', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});

it('rejects unauthenticated create', function () {
    $response = $this->postJson('/api/projects', ['name' => 'Nope']);

    $response->assertUnauthorized();
});

it('updates a project name', function () {
    $project = Project::factory()->create(['name' => 'Old']);

    $response = $this->actingAs(User::factory()->create())->patchJson("/api/projects/{$project->id}", [
        'name' => 'New Name',
    ]);

    $response->assertOk()->assertJsonPath('data.name', 'New Name');
});

it('returns 404 when updating an unknown project', function () {
    $response = $this->actingAs(User::factory()->create())->patchJson('/api/projects/999999', [
        'name' => 'X',
    ]);

    $response->assertNotFound();
});

it('deletes a project and returns the deleted resource', function () {
    $project = Project::factory()->create();

    $response = $this->actingAs(User::factory()->create())->deleteJson("/api/projects/{$project->id}");

    $response->assertOk()->assertJsonPath('data.id', (string) $project->id);
    expect(Project::find($project->id))->toBeNull();
});

it('returns 404 when deleting an unknown project', function () {
    $response = $this->actingAs(User::factory()->create())->deleteJson('/api/projects/999999');

    $response->assertNotFound();
});
