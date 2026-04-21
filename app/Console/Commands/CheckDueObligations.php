<?php

namespace App\Console\Commands;

use App\Models\Obligation;
use App\Notifications\ObligationDueNotification;
use Illuminate\Console\Command;

class CheckDueObligations extends Command
{
    protected $signature = 'obligations:check-due';

    protected $description = 'Check for due and overdue obligations and send reminder emails';

    public function handle(): int
    {
        $today = now()->toDateString();
        $threeDaysFromNow = now()->addDays(3)->toDateString();

        $dueSoon = Obligation::whereNotNull('email')
            ->where('status', '!=', 'remitted')
            ->where('status', '!=', 'received')
            ->whereBetween('due_date', [$today, $threeDaysFromNow])
            ->get();

        foreach ($dueSoon as $obligation) {
            $obligation->notify(new ObligationDueNotification($obligation, false));
            $this->info("Sent due reminder for: {$obligation->title}");
        }

        $overdue = Obligation::whereNotNull('email')
            ->where('status', '!=', 'remitted')
            ->where('status', '!=', 'received')
            ->where('due_date', '<', $today)
            ->get();

        foreach ($overdue as $obligation) {
            $obligation->notify(new ObligationDueNotification($obligation, true));
            $this->info("Sent overdue alert for: {$obligation->title}");
        }

        $this->info('Due obligation check complete.');

        return Command::SUCCESS;
    }
}
