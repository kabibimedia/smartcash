<?php

namespace App\Console\Commands;

use App\Models\Obligation;
use App\Models\Receipt;
use App\Models\Reminder;
use App\Models\Remittance;
use Illuminate\Console\Command;

class WipeAllData extends Command
{
    protected $signature = 'data:wipe-all';

    protected $description = 'Wipe all data but keep admin user';

    public function handle(): int
    {
        Obligation::whereNotNull('id')->delete();
        $obCount = Obligation::count();

        Receipt::whereNotNull('id')->delete();
        $recCount = Receipt::count();

        Remittance::whereNotNull('id')->delete();
        $remCount = Remittance::count();

        Reminder::whereNotNull('id')->delete();
        $reminderCount = Reminder::count();

        $this->info("Deleted: {$obCount} obligations, {$recCount} receipts, {$remCount} remittances, {$reminderCount} reminders");
        $this->info('All data wiped. Admin credentials preserved.');

        return Command::SUCCESS;
    }
}
