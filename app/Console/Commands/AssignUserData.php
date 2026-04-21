<?php

namespace App\Console\Commands;

use App\Models\Obligation;
use App\Models\Receipt;
use App\Models\Remittance;
use App\Models\User;
use Illuminate\Console\Command;

class AssignUserData extends Command
{
    protected $signature = 'data:assign-user';

    protected $description = 'Assign existing data to users based on their email';

    public function handle(): int
    {
        $users = User::all();

        foreach ($users as $user) {
            $obCount = 0;
            $recCount = 0;
            $remCount = 0;

            Obligation::whereNull('user_id')
                ->where('email', $user->email)
                ->update(['user_id' => $user->id]);

            $obCount = Obligation::where('user_id', $user->id)->count();

            Receipt::whereNull('user_id')
                ->where('email', $user->email)
                ->update(['user_id' => $user->id]);

            $recCount = Receipt::where('user_id', $user->id)->count();

            Remittance::whereNull('user_id')
                ->where('email', $user->email)
                ->update(['user_id' => $user->id]);

            $remCount = Remittance::where('user_id', $user->id)->count();

            $this->info("User {$user->name}: {$obCount} obligations, {$recCount} receipts, {$remCount} remittances");
        }

        $noUserObs = Obligation::whereNull('user_id')->count();
        $noUserRecs = Receipt::whereNull('user_id')->count();
        $noUserRems = Remittance::whereNull('user_id')->count();

        if ($noUserObs > 0 || $noUserRecs > 0 || $noUserRems > 0) {
            $this->warn("Unassigned: {$noUserObs} obligations, {$noUserRecs} receipts, {$noUserRems} remittances");
        }

        $this->info('Done!');

        return Command::SUCCESS;
    }
}
