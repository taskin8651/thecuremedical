<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class CustomerVerifyEmail extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'customer.verification.verify',
            now()->addHour(),
            ['customer' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );

        return (new MailMessage)
            ->subject('Verify your customer account email')
            ->line('Please verify your email address to use checkout and view customer orders.')
            ->action('Verify email address', $url)
            ->line('This link expires in 60 minutes.');
    }
}
