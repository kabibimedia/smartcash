<?php

namespace App\Notifications;

use App\Models\Obligation;
use App\Models\Remittance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RemittanceAddedNotification extends Notification
{
    use Queueable;

    public function __construct(public Obligation $obligation, public Remittance $remittance) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Remittance Processed - SmartCash')
            ->line('A remittance has been processed for an obligation.')
            ->line('Obligation: '.$this->obligation->title)
            ->line('Amount Remitted: GHS '.number_format($this->remittance->amount_paid, 2))
            ->line('Remittance Date: '.$this->remittance->formatted_date)
            ->action('View Obligation', url('/remittances'));
    }
}
