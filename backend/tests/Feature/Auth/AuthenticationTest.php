<?php

use App\Models\Learner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

describe('POST /v1/auth/login', function () {
    it('authenticates a manually provisioned learner and establishes a session', function () {
        $learner = Learner::factory()->create([
            'email' => 'learner@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        $response = $this->postJson('/v1/auth/login', [
            'email' => 'learner@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJson([
                'id' => $learner->id,
                'email' => 'learner@example.com',
                'display_name' => $learner->display_name,
            ])
            ->assertJsonMissingPath('password_hash');

        $this->assertAuthenticatedAs($learner);

        $this->getJson('/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $learner->id);
    });

    it('returns 401 with the approved error contract on invalid credentials', function () {
        Learner::factory()->create([
            'email' => 'learner@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        $this->postJson('/v1/auth/login', [
            'email' => 'learner@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJson([
                'error' => [
                    'code' => 'invalid_credentials',
                    'message' => 'Invalid credentials.',
                ],
            ]);
    });

    it('returns 422 with validation fields for malformed requests', function () {
        $this->postJson('/v1/auth/login', [
            'email' => 'not-an-email',
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonFragment([
                'field' => 'email',
                'message' => 'The email field must be a valid email address.',
            ])
            ->assertJsonFragment([
                'field' => 'password',
                'message' => 'The password field is required.',
            ]);
    });
});

describe('GET /v1/auth/me', function () {
    it('returns the authenticated learner without exposing the password hash', function () {
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->getJson('/v1/auth/me')
            ->assertOk()
            ->assertJson([
                'id' => $learner->id,
                'email' => $learner->email,
                'display_name' => $learner->display_name,
            ])
            ->assertJsonMissingPath('password_hash');
    });

    it('returns the approved 401 error contract for unauthenticated requests', function () {
        $this->getJson('/v1/auth/me')
            ->assertStatus(401)
            ->assertJson([
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => 'Authentication required.',
                ],
            ]);
    });
});

describe('POST /v1/auth/logout', function () {
    it('logs out an authenticated learner and invalidates the session', function () {
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->postJson('/v1/auth/logout')
            ->assertNoContent();

        $this->assertGuest();

        $this->getJson('/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');
    });

    it('requires authentication', function () {
        $this->postJson('/v1/auth/logout')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');
    });
});

describe('Learner identity model', function () {
    it('uses the learner auth provider with UUID identifiers and password_hash credentials', function () {
        $learner = Learner::factory()->create();

        expect(Config::get('auth.providers.learners.model'))->toBe(Learner::class)
            ->and($learner->getAuthPasswordName())->toBe('password_hash')
            ->and($learner->id)->toBeString()
            ->and(Str::isUuid($learner->id))->toBeTrue();
    });
});
