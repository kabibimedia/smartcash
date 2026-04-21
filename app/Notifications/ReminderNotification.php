<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Reminder $reminder) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $repeat = $this->reminder->repeat_type ? " (Repeats {$this->reminder->repeat_type})" : '';

        return (new MailMessage)
            ->subject('Reminder: '.$this->reminder->title.' - SmartCash')
            ->line('You have a reminder!')
            ->line('**'.$this->reminder->title.'**')
            ->line('Time: '.$this->reminder->formatted_reminder_at.$repeat)
            ->when($this->reminder->description, fn ($m) => $m->line($this->reminder->description));
    }
}
