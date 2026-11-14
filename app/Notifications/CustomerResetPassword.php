<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerResetPassword extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('customer.password.reset', ['token' => $this->token, 'email' => $notifiable->email]);

        return (new MailMessage)
            ->subject('Reset your customer account password')
            ->line('We received a request to reset your customer account password.')
            ->action('Reset password', $url)
            ->line('This link expires in '.config('auth.passwords.customers.expire').' minutes. If you did not request it, ignore this email.');
    }
}
