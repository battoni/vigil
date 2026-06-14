<?php

use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function actingAsWithRole(array $permissions = []): User
{
    $user = User::factory()->create();
    foreach ($permissions as $key) {
        UserPermission::create(['user_id' => $user->id, 'permission_key' => $key, 'value' => true]);
    }

    return $user;
}

function createRole(string $name = 'Test Role'): Role
{
    return Role::create(['name' => $name]);
}

// ---------------------------------------------------------------------------
// GET /api/auth/roles
// ---------------------------------------------------------------------------

describe('GET /api/auth/roles', function () {
    it('returns list of roles', function () {
        createRole('Admin');
        createRole('Support');

        $response = $this->getJson('/api/auth/roles');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });
});

// ---------------------------------------------------------------------------
// GET /api/auth/roles/{id}
// ---------------------------------------------------------------------------

describe('GET /api/auth/roles/{id}', function () {
    it('returns a single role', function () {
        $role = createRole('Admin');

        $response = $this->getJson("/api/auth/roles/{$role->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', (string) $role->id)
            ->assertJsonPath('data.name', 'Admin');
    });

    it('returns 404 for unknown role', function () {
        $response = $this->getJson('/api/auth/roles/999999');

        $response->assertNotFound();
    });
});

// ---------------------------------------------------------------------------
// POST /api/auth/roles
// ---------------------------------------------------------------------------

describe('POST /api/auth/roles', function () {
    it('creates a role when authenticated', function () {
        $actor = actingAsWithRole(['roles.create']);

        $response = $this->actingAs($actor)->postJson('/api/auth/roles', [
            'name' => 'New Role',
            'description' => 'A description',
            'permission_group_ids' => ['users'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Role');
    });

    it('rejects unauthenticated requests', function () {
        $response = $this->postJson('/api/auth/roles', [
            'name' => 'New Role',
            'permission_group_ids' => [],
        ]);

        $response->assertUnauthorized();
    });

    it('validates required fields', function () {
        $actor = actingAsWithRole(['roles.create']);

        $response = $this->actingAs($actor)->postJson('/api/auth/roles', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
});

// ---------------------------------------------------------------------------
// PATCH /api/auth/roles/{id}
// ---------------------------------------------------------------------------

describe('PATCH /api/auth/roles/{id}', function () {
    it('updates a role name when authenticated', function () {
        $actor = actingAsWithRole(['roles.update']);
        $role = createRole('Old Name');

        $response = $this->actingAs($actor)->patchJson("/api/auth/roles/{$role->id}", [
            'name' => 'Updated Name',
            'description' => '',
            'permission_group_ids' => ['users'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    });

});

// ---------------------------------------------------------------------------
// PATCH /api/auth/roles/{id}/permissions
// ---------------------------------------------------------------------------

describe('PATCH /api/auth/roles/{id}/permissions', function () {
    it('updates role permissions when authenticated', function () {
        $actor = actingAsWithRole(['roles.update']);
        $role = createRole('Admin');

        $response = $this->actingAs($actor)->patchJson("/api/auth/roles/{$role->id}/permissions", [
            'permissions' => ['users.read' => true, 'users.delete' => false],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', (string) $role->id);
    });

    it('rejects unauthenticated requests', function () {
        $role = createRole('Admin');

        $response = $this->patchJson("/api/auth/roles/{$role->id}/permissions", [
            'permissions' => ['users.read' => true],
        ]);

        $response->assertUnauthorized();
    });
});

// ---------------------------------------------------------------------------
// DELETE /api/auth/roles/{id}
// ---------------------------------------------------------------------------

describe('DELETE /api/auth/roles/{id}', function () {
    it('deletes a role when authenticated', function () {
        $actor = actingAsWithRole(['roles.delete']);
        $role = createRole('Deletable Role');

        $response = $this->actingAs($actor)->deleteJson("/api/auth/roles/{$role->id}");

        $response->assertOk();
        expect(Role::find($role->id))->toBeNull();
    });

    it('rejects unauthenticated requests', function () {
        $role = createRole('Protected Role');

        $response = $this->deleteJson("/api/auth/roles/{$role->id}");

        $response->assertUnauthorized();
    });

    it('returns 404 for unknown role', function () {
        $actor = actingAsWithRole(['roles.delete']);

        $response = $this->actingAs($actor)->deleteJson('/api/auth/roles/999999');

        $response->assertNotFound();
    });
});
