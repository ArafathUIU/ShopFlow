<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => $request->string('password'),
            'role' => UserRole::Customer,
        ]);

        $user->sendEmailVerificationNotification();

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $this->auth->issueToken($user),
        ], 'Account created.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            return ApiResponse::error('These credentials do not match our records.', 422);
        }

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $this->auth->issueToken($user),
        ], 'Logged in.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->revokeCurrentToken($request->user());

        return ApiResponse::success(null, 'Logged out.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()), 'OK');
    }

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::error('Email already verified.', 400);
        }

        $user->sendEmailVerificationNotification();

        return ApiResponse::success(null, 'Verification link sent.');
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'hash' => ['required', 'string'],
        ]);

        $user = User::query()->find((int) $validated['id']);

        if (! $user || $request->user()->id !== $user->id) {
            return ApiResponse::error('Invalid verification link.', 403);
        }

        if (! $this->auth->verifyEmail($user, $validated['hash'])) {
            return ApiResponse::error('Invalid verification link.', 403);
        }

        return ApiResponse::success(new UserResource($user), 'Email verified.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->auth->sendPasswordResetLink($request->string('email'));

        return ApiResponse::success(null, 'If that email address exists, a password reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->auth->resetPassword($request->only('email', 'password', 'password_confirmation', 'token'));

        return match ($status) {
            Password::PASSWORD_RESET => ApiResponse::success(null, 'Password reset successfully.'),
            Password::INVALID_TOKEN => ApiResponse::error('This password reset token is invalid.', 400),
            Password::INVALID_USER => ApiResponse::error('We could not find a user with that email address.', 400),
            default => ApiResponse::error('Unable to reset your password.', 400),
        };
    }
}
