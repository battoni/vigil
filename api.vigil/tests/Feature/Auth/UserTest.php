<?php

use App\Models\User;
use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper: create a user with specific direct permissions (value=true = allow).
function userWithPermissions(array $permissionKeys): User
{
    $user = User::factory()->create();
    foreach ($permissionKeys as $key) {
        UserPermission::create(['user_id' => $user->id, 'permission_key' => $key, 'value' => true]);
    }

    return $user;
}

// ---------------------------------------------------------------------------
// GET /api/auth/users
// ---------------------------------------------------------------------------

describe('GET /api/auth/users', function () {
    it('returns users list for an authorised user', function () {
        $actor = userWithPermissions(['users.read']);
        User::factory()->count(3)->create();

        $this->actingAs($actor)
            ->getJson('/api/auth/users')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'username']]]);
    });

    it('returns 403 when user lacks users.read permission', function () {
        $actor = User::factory()->create();

        $this->actingAs($actor)
            ->getJson('/api/auth/users')
            ->assertForbidden();
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson('/api/auth/users')->assertUnauthorized();
    });

    it('filters by ids when provided', function () {
        $actor = userWithPermissions(['users.read']);
        $target = User::factory()->create();
        User::factory()->count(2)->create();

        $response = $this->actingAs($actor)
            ->getJson("/api/auth/users?ids[]={$target->id}")
            ->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1)
            ->and($data[0]['id'])->toBe($target->id);
    });
});

// ---------------------------------------------------------------------------
// GET /api/auth/users/{id}
// ---------------------------------------------------------------------------

describe('GET /api/auth/users/{id}', function () {
    it('returns a single user for an authorised actor', function () {
        $actor = userWithPermissions(['users.read']);
        $target = User::factory()->create(['username' => 'dave']);

        $this->actingAs($actor)
            ->getJson("/api/auth/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $target->id)
            ->assertJsonPath('data.username', 'dave');
    });

    it('returns 404 when user does not exist', function () {
        $actor = userWithPermissions(['users.read']);

        $this->actingAs($actor)
            ->getJson('/api/auth/users/99999')
            ->assertNotFound();
    });

    it('returns 403 when actor lacks users.read', function () {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($actor)
            ->getJson("/api/auth/users/{$target->id}")
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// POST /api/auth/users
// ---------------------------------------------------------------------------

describe('POST /api/auth/users', function () {
    it('creates and returns a new user', function () {
        $actor = userWithPermissions(['users.create']);
        $role = Role::create(['name' => 'Test Role', 'slug' => 'test-role']);

        $payload = [
            'name' => 'Eve',
            'last_name' => 'Green',
            'username' => 'eve',
            'role_id' => $role->id,
            'password' => 'password123',
            'status' => UserStatus::ACTIVE->value,
        ];

        $this->actingAs($actor)
            ->postJson('/api/auth/users', $payload)
            ->assertCreated()
            ->assertJsonPath('data.username', 'eve');

        $this->assertDatabaseHas('users', ['username' => 'eve']);
    });

    it('returns 403 when actor lacks users.create', function () {
        $actor = User::factory()->create();

        $this->actingAs($actor)
            ->postJson('/api/auth/users', ['username' => 'x'])
            ->assertForbidden();
    });

    it('returns 422 when required fields are missing', function () {
        $actor = userWithPermissions(['users.create']);

        $this->actingAs($actor)
            ->postJson('/api/auth/users', [])
            ->assertUnprocessable();
    });
});

// ---------------------------------------------------------------------------
// PATCH /api/auth/users/{id}
// ---------------------------------------------------------------------------

describe('PATCH /api/auth/users/{id}', function () {
    it('updates and returns the modified user', function () {
        $actor = userWithPermissions(['users.update']);
        $target = User::factory()->create(['name' => 'Frank']);

        $this->actingAs($actor)
            ->patchJson("/api/auth/users/{$target->id}", ['name' => 'Franklin'])
            ->assertOk()
            ->assertJsonPath('data.id', $target->id);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Franklin']);
    });

    it('returns 404 when target user does not exist', function () {
        $actor = userWithPermissions(['users.update']);

        $this->actingAs($actor)
            ->patchJson('/api/auth/users/99999', ['name' => 'Nobody'])
            ->assertNotFound();
    });

    it('returns 403 when actor lacks users.update', function () {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($actor)
            ->patchJson("/api/auth/users/{$target->id}", ['name' => 'X'])
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// PATCH /api/auth/users/{id}/archive
// ---------------------------------------------------------------------------

describe('PATCH /api/auth/users/{id}/archive', function () {
    it('toggles status from active to inactive', function () {
        $actor = userWithPermissions(['users.archive']);
        $target = User::factory()->create(['status' => UserStatus::ACTIVE]);

        $this->actingAs($actor)
            ->patchJson("/api/auth/users/{$target->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', UserStatus::INACTIVE->value);
    });

    it('toggles status from inactive to active', function () {
        $actor = userWithPermissions(['users.archive']);
        $target = User::factory()->create(['status' => UserStatus::INACTIVE]);

        $this->actingAs($actor)
            ->patchJson("/api/auth/users/{$target->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', UserStatus::ACTIVE->value);
    });

    it('returns 404 when user does not exist', function () {
        $actor = userWithPermissions(['users.archive']);

        $this->actingAs($actor)
            ->patchJson('/api/auth/users/99999/archive')
            ->assertNotFound();
    });
});

// ---------------------------------------------------------------------------
// DELETE /api/auth/users/{id}
// ---------------------------------------------------------------------------

describe('DELETE /api/auth/users/{id}', function () {
    it('deletes the user and returns the resource', function () {
        $actor = userWithPermissions(['users.delete']);
        $target = User::factory()->create();

        $this->actingAs($actor)
            ->deleteJson("/api/auth/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $target->id);

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    });

    it('returns 404 when user does not exist', function () {
        $actor = userWithPermissions(['users.delete']);

        $this->actingAs($actor)
            ->deleteJson('/api/auth/users/99999')
            ->assertNotFound();
    });

    it('returns 403 when actor lacks users.delete', function () {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($actor)
            ->deleteJson("/api/auth/users/{$target->id}")
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// GET /api/auth/users/check-username
// ---------------------------------------------------------------------------

describe('GET /api/auth/users/check-username', function () {
    it('returns available when username is not taken', function () {
        $actor = User::factory()->create();

        $this->actingAs($actor)
            ->getJson('/api/auth/users/check-username?username=unused_name')
            ->assertOk()
            ->assertJsonPath('data.available', true);
    });

    it('returns 409 when username is already taken', function () {
        $actor = User::factory()->create();
        User::factory()->create(['username' => 'taken_name']);

        $this->actingAs($actor)
            ->getJson('/api/auth/users/check-username?username=taken_name')
            ->assertConflict();
    });

    it('returns 400 when username query param is missing', function () {
        $actor = User::factory()->create();

        $this->actingAs($actor)
            ->getJson('/api/auth/users/check-username')
            ->assertBadRequest();
    });

    it('excludes the specified id from the uniqueness check', function () {
        $actor = User::factory()->create();
        $existing = User::factory()->create(['username' => 'my_name']);

        $this->actingAs($actor)
            ->getJson("/api/auth/users/check-username?username=my_name&exclude_id={$existing->id}")
            ->assertOk()
            ->assertJsonPath('data.available', true);
    });
});
