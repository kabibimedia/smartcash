<?php

namespace App\Console\Commands;

use App\Models\Obligation;
use App\Models\User;
use App\Notifications\ObligationCreatedNotification;
use Illuminate\Console\Command;

class SendMissingEmails extends Command
{
    protected $signature = 'email:send-missing';

    protected $description = 'Send missing notification emails';

    public function handle(): int
    {
        $obligations = Obligation::where('user_id', null)->whereNotNull('email')->get();

        foreach ($obligations as $ob) {
            try {
                $ob->notify(new ObligationCreatedNotification($ob));
                $this->info("Sent obligation email: {$ob->title}");
            } catch (\Exception $e) {
                $this->error('Failed: '.$e->getMessage());
            }
        }

        $noEmailObs = Obligation::where('user_id', null)->whereNull('email')->get();
        foreach ($noEmailObs as $ob) {
            $user = User::where('email', 'maameaba712@gmail.com')->first();
            if ($user && $user->email) {
                try {
                    $ob->update(['user_id' => $user->id]);
                    $user->notify(new ObligationCreatedNotification($ob->fresh()));
                    $this->info("Sent to user {$user->email}: {$ob->title}");
                } catch (\Exception $e) {
                    $this->error('Failed: '.$e->getMessage());
                }
            }
        }

        $this->info('Done!');

        return Command::SUCCESS;
    }
}
