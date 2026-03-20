<?php

namespace App\Providers;

use App\Mail\ResetPasswordMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class PasswordResetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $notification = new \Filament\Notifications\Auth\ResetPassword($token);
            $url = \Filament\Facades\Filament::getResetPasswordUrl($token, $notifiable);

            $mailable = new ResetPasswordMail($url);

            return $mailable->to($notifiable->email);
        });
    }
}
