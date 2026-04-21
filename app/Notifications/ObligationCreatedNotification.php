<?php

namespace App\Notifications;

use App\Models\Obligation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ObligationCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Obligation $obligation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Obligation Created - SmartCash')
            ->line('A new obligation has been created.')
            ->line('Title: '.$this->obligation->title)
            ->line('Amount: GHS '.number_format($this->obligation->amount_expected, 2))
            ->line('Due Date: '.$this->obligation->formatted_due_date)
            ->line('Status: '.$this->obligation->status)
            ->action('View Obligation', url('/obligations'));
    }
}
