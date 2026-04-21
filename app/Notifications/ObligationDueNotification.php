<?php

namespace App\Notifications;

use App\Models\Obligation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ObligationDueNotification extends Notification
{
    use Queueable;

    public function __construct(public Obligation $obligation, public bool $isOverdue) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->isOverdue
            ? 'OVERDUE: Obligation Payment Due - SmartCash'
            : 'REMINDER: Obligation Due Soon - SmartCash';

        $message = (new MailMessage)
            ->subject($subject)
            ->line($this->isOverdue
                ? 'URGENT: Your obligation is OVERDUE!'
                : 'Reminder: Your obligation is due soon.')
            ->line('Title: '.$this->obligation->title)
            ->line('Amount Due: GHS '.number_format($this->obligation->amount_expected, 2))
            ->line('Due Date: '.$this->obligation->formatted_due_date)
            ->line('Status: '.$this->obligation->status);

        if ($this->obligation->amount_received > 0) {
            $message->line('Amount Received: GHS '.number_format($this->obligation->amount_received, 2));
            $message->line('Outstanding: GHS '.number_format($this->obligation->outstanding, 2));
        }

        return $message->action('View Obligation', url('/obligations'));
    }
}
