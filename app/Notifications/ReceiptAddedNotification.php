<?php

namespace App\Notifications;

use App\Models\Obligation;
use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReceiptAddedNotification extends Notification
{
    use Queueable;

    public function __construct(public Obligation $obligation, public Receipt $receipt) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->obligation->status === 'received' ? ' (FULLY PAID)' : ' (PARTIAL)';

        return (new MailMessage)
            ->subject('Receipt Added - SmartCash')
            ->line('A receipt has been added to an obligation.')
            ->line('Obligation: '.$this->obligation->title)
            ->line('Amount Received: GHS '.number_format($this->receipt->amount_received, 2))
            ->line('Receipt Date: '.$this->receipt->formatted_date_received)
            ->line('New Status: '.$this->obligation->status.$status)
            ->action('View Obligation', url('/obligations'));
    }
}
