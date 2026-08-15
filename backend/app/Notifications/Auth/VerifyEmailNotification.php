<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Support\Arr;

/**
 * Builds the email verification link that points at the storefront
 * (e.g. https://shopflow.app/verify-email?id=1&hash=...). The frontend then
 * confirms the address against POST /api/v1/auth/email/verify.
 */
class VerifyEmailNotification extends BaseVerifyEmail
{
    public function verificationUrl($notifiable): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return $frontendUrl.'/verify-email?'.Arr::query([
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);
    }
}
