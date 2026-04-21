<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(public User $user) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to SmartCash')
            ->line('Welcome '.$this->user->name.'!')
            ->line('Your account has been created successfully.')
            ->line('You can now start managing your obligations, receipts, and remittances.')
            ->action('Go to Dashboard', url('/dashboard'));
    }
}
