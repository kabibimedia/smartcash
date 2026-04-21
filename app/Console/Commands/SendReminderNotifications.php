<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Console\Command;

class SendReminderNotifications extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send reminder notifications';

    public function handle(): int
    {
        $now = now();
        $reminders = Reminder::where('is_active', true)
            ->whereBetween('reminder_at', [
                $now->copy()->subMinutes(5),
                $now->copy()->addMinute(),
            ])
            ->get();

        foreach ($reminders as $reminder) {
            $user = $reminder->user;

            if (! $user) {
                $user = User::find($reminder->user_id);
            }

            $emails = [];

            if ($user && $user->email) {
                $emails[] = $user->email;
            }

            if ($reminder->email && ! in_array($reminder->email, $emails)) {
                $emails[] = $reminder->email;
            }

            if (empty($emails)) {
                $this->info("No email for reminder: {$reminder->title}");

                continue;
            }

            foreach ($emails as $email) {
                $reminder->notify(new ReminderNotification($reminder));
                $this->info("Sent reminder: {$reminder->title} to {$email}");
            }
        }

        $this->info("Processed {$reminders->count()} reminders.");

        return Command::SUCCESS;
    }
}
