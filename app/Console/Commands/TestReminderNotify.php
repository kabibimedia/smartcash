<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Console\Command;

class TestReminderNotify extends Command
{
    protected $signature = 'reminder:test';

    protected $description = 'Test send reminder';

    public function handle(): int
    {
        $reminder = Reminder::first();

        if (! $reminder) {
            $this->error('No reminder found');

            return Command::FAILURE;
        }

        $user = User::where('email', 'maameaba712@gmail.com')->first();

        if ($user) {
            $reminder->user()->associate($user);
            $reminder->save();
            $user->notify(new ReminderNotification($reminder));
            $this->info('Sent to maameaba712@gmail.com');
        }

        return Command::SUCCESS;
    }
}
