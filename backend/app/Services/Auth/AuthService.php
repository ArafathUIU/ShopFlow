<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;

final class AuthService
{
    public function issueToken(User $user, string $name = 'access-token', array $abilities = ['*']): string
    {
        return $user->createToken($name, $abilities)->plainTextToken;
    }

    public function revokeCurrentToken(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function verifyEmail(User $user, string $hash): bool
    {
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return true;
    }

    public function sendPasswordResetLink(string $email): string
    {
        return Password::broker()->sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $credentials): string
    {
        return Password::broker()->reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );
    }
}
