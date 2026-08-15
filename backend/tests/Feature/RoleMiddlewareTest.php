<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::middleware('auth:sanctum')
        ->middleware('role:admin')
        ->get('/api/v1/_test/admin', fn () => response()->json(['ok' => true]));

    Route::middleware('auth:sanctum')
        ->middleware('role:admin,manager')
        ->get('/api/v1/_test/staff', fn () => response()->json(['ok' => true]));
});

it('allows a user with the required role', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/_test/admin')
        ->assertOk()
        ->assertJson(['ok' => true]);
});

it('denies a user without the required role', function (): void {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/_test/admin')
        ->assertForbidden();
});

it('allows any of the listed roles', function (): void {
    $manager = User::factory()->create(['role' => UserRole::Manager]);

    $this->actingAs($manager, 'sanctum')
        ->getJson('/api/v1/_test/staff')
        ->assertOk()
        ->assertJson(['ok' => true]);
});
