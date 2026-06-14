<?php

use App\Models\User;
use App\Modules\Auth\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// POST /api/auth/login
// ---------------------------------------------------------------------------

describe('POST /api/auth/login', function () {
    it('returns the authenticated user on valid credentials', function () {
        $user = User::factory()->create([
            'username' => 'alice',
            'password' => Hash::make('secret123'),
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'alice',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.username', 'alice');
    });

    it('returns 422 when username does not exist', function () {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'nobody',
            'password' => 'secret',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    });

    it('returns 422 when password is incorrect', function () {
        User::factory()->create([
            'username' => 'bob',
            'password' => Hash::make('correctpass'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'bob',
            'password' => 'wrongpass',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    });

    it('returns 422 when required fields are missing', function () {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'password']);
    });
});

// ---------------------------------------------------------------------------
// POST /api/auth/logout
// ---------------------------------------------------------------------------

describe('POST /api/auth/logout', function () {
    it('logs out an authenticated user and returns success', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/auth/logout')
            ->assertOk();
    });

    it('returns 401 when not authenticated', function () {
        $this->postJson('/api/auth/logout')
            ->assertUnauthorized();
    });
});

// ---------------------------------------------------------------------------
// GET /api/auth/me
// ---------------------------------------------------------------------------

describe('GET /api/auth/me', function () {
    it('returns the authenticated user', function () {
        $user = User::factory()->create(['username' => 'carol']);

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.username', 'carol');
    });

    it('returns 401 when not authenticated', function () {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized();
    });

    it('includes a permissions array in the response', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'username', 'permissions']]);
    });
});

// ---------------------------------------------------------------------------
// GET /api/auth/support-names
// ---------------------------------------------------------------------------

describe('GET /api/auth/support-names', function () {
    it('returns an empty array when no active users have reset_password permission', function () {
        $this->getJson('/api/auth/support-names')
            ->assertOk()
            ->assertJsonPath('data', []);
    });
});
