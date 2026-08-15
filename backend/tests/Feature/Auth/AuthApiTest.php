<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('registers a customer and returns a token', function (): void {
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.email', 'jane@example.com')
        ->assertJsonPath('data.user.role', 'customer')
        ->assertJsonStructure(['data' => ['token']]);

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role' => 'customer']);

    $user = User::query()->where('email', 'jane@example.com')->first();
    expect(Hash::check('password123', $user->password))->toBeTrue();
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('rejects registration when email is already taken', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['errors' => ['email']]);
});

it('rejects registration with a weak password', function (): void {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertStatus(422)
        ->assertJsonStructure(['errors' => ['password']]);
});

it('logs a user in and issues a token', function (): void {
    $user = User::factory()->create(['password' => 'password123']);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ])->assertOk()
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonStructure(['data' => ['token']]);
});

it('rejects login with invalid credentials', function (): void {
    $user = User::factory()->create(['password' => 'password123']);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('returns the authenticated user from me', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.role', 'admin')
        ->assertJsonMissing(['password']);
});

it('rejects me when unauthenticated', function (): void {
    $this->getJson('/api/v1/auth/me')->assertStatus(401);
});

it('revokes the current token on logout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out.');

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('resends a verification notification for an unverified user', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/email/verification-notification')
        ->assertOk();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('refuses to resend verification for a verified user', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/email/verification-notification')
        ->assertStatus(400);

    Notification::assertNotSentTo($user, VerifyEmailNotification::class);
});

it('verifies an email address with a matching hash', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/email/verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ])->assertOk()
        ->assertJsonPath('message', 'Email verified.');

    $this->assertNotNull($user->fresh()->email_verified_at);
});

it('rejects email verification with an invalid hash', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/email/verify', [
            'id' => $user->id,
            'hash' => 'not-the-right-hash',
        ])->assertStatus(403);

    $this->assertNull($user->fresh()->email_verified_at);
});

it('sends a password reset link', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('success', true);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('does not reveal whether an email exists', function (): void {
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ghost@example.com'])
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('resets the password and revokes existing tokens', function (): void {
    $user = User::factory()->create(['password' => 'old-password']);
    $user->createToken('previous')->plainTextToken;

    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ])->assertOk()
        ->assertJsonPath('message', 'Password reset successfully.');

    $fresh = $user->fresh();
    expect(Hash::check('new-password123', $fresh->password))->toBeTrue();
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('rejects password reset with an invalid token', function (): void {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ])->assertStatus(400);
});
