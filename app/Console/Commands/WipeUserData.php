<?php

namespace App\Console\Commands;

use App\Models\Obligation;
use App\Models\Receipt;
use App\Models\Reminder;
use App\Models\Remittance;
use App\Models\User;
use Illuminate\Console\Command;

class WipeUserData extends Command
{
    protected $signature = 'data:wipe-users';

    protected $description = 'Remove all non-admin users and their data';

    public function handle(): int
    {
        $admin = User::where('email', 'admin@smartcash.com')->first();

        if (! $admin) {
            $this->error('Admin user not found!');

            return Command::FAILURE;
        }

        $userIds = User::where('email', '!=', 'admin@smartcash.com')->pluck('id');

        $obCount = Obligation::whereIn('user_id', $userIds)->delete();
        $recCount = Receipt::whereIn('user_id', $userIds)->delete();
        $remCount = Remittance::whereIn('user_id', $userIds)->delete();
        $reminderCount = Reminder::whereIn('user_id', $userIds)->delete();

        User::where('email', '!=', 'admin@smartcash.com')->delete();

        $this->info("Deleted: {$obCount} obligations, {$recCount} receipts, {$remCount} remittances, {$reminderCount} reminders");
        $this->info('Kept admin user only.');

        return Command::SUCCESS;
    }
}
