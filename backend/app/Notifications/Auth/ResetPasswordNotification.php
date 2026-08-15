<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Support\Arr;

/**
 * Sends a password reset link pointing at the storefront
 * (e.g. https://shopflow.app/reset-password?token=...&email=...).
 */
class ResetPasswordNotification extends BaseResetPassword
{
    protected function resetUrl($notifiable): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return $frontendUrl.'/reset-password?'.Arr::query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
