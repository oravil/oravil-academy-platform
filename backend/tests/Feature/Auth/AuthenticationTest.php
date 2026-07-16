<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('POST /api/login', function () {
    it('returns 200 with token and user on valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'created_at'],
                'token',
            ])
            ->assertJsonPath('user.email', 'test@example.com');

        expect($response->json('token'))->not->toBeNull();
    });

    it('returns 422 on invalid credentials', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Invalid credentials.');
    });

    it('returns 422 on missing fields', function () {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    });
});

describe('GET /api/me', function () {
    it('returns 200 with user data when authenticated', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'email', 'created_at'])
            ->assertJsonPath('email', $user->email);
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson('/api/me')
            ->assertStatus(401);
    });
});

describe('POST /api/logout', function () {
    it('returns 204 and revokes token when authenticated', function () {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertStatus(204);

        expect($user->tokens()->count())->toBe(0);
    });
});
