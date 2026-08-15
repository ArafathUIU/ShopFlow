<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/api/v1/_test/authenticated', fn () => response()->json(['ok' => true]));
    });

    Route::middleware('auth:sanctum')
        ->middleware('role:admin')
        ->get('/api/v1/_test/admin-only', fn () => response()->json(['ok' => true]));

    Route::post('/api/v1/_test/validated', function (): void {
        request()->validate(['name' => ['required']]);
    });
});

it('returns a uniform 404 envelope for unknown routes', function (): void {
    $this->getJson('/api/v1/does-not-exist')
        ->assertStatus(404)
        ->assertExactJson([
            'success' => false,
            'message' => 'Route not found.',
        ]);
});

it('returns a uniform 405 envelope for wrong methods', function (): void {
    $this->postJson('/api/v1/_test/authenticated')
        ->assertStatus(405)
        ->assertExactJson([
            'success' => false,
            'message' => 'Method not allowed.',
        ]);
});

it('returns a uniform 401 envelope when unauthenticated', function (): void {
    $this->getJson('/api/v1/_test/authenticated')
        ->assertStatus(401)
        ->assertExactJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
});

it('returns a uniform 422 envelope with field errors', function (): void {
    $this->postJson('/api/v1/_test/validated')
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'The given data was invalid.',
            'errors' => [
                'name' => ['The name field is required.'],
            ],
        ]);
});

it('returns a uniform 403 envelope for forbidden actions', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/_test/admin-only')
        ->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You are not authorized to perform this action.',
        ]);
});
