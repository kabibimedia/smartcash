<?php

namespace App\Console\Commands;

use App\Models\Obligation;
use App\Notifications\ObligationCreatedNotification;
use Illuminate\Console\Command;

class SendExistingEmails extends Command
{
    protected $signature = 'obligations:send-existing';

    protected $description = 'Send notification emails to existing obligations';

    public function handle(): int
    {
        $obligations = Obligation::whereNotNull('email')->get();

        foreach ($obligations as $obligation) {
            $obligation->notify(new ObligationCreatedNotification($obligation));
            $this->info("Sent email for: {$obligation->title} to {$obligation->email}");
        }

        $this->info("Done! Sent {$obligations->count()} emails.");

        return Command::SUCCESS;
    }
}
