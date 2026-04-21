<?php

namespace App\Console\Commands;

use App\Models\Obligation;
use App\Models\Receipt;
use App\Models\Remittance;
use Illuminate\Console\Command;

class AssignToAdmin extends Command
{
    protected $signature = 'data:assign-admin';

    protected $description = 'Assign unassigned data to admin';

    public function handle(): int
    {
        Obligation::whereNull('user_id')->update(['user_id' => 0]);
        $obCount = Obligation::where('user_id', 0)->count();

        Receipt::whereNull('user_id')->update(['user_id' => 0]);
        $recCount = Receipt::where('user_id', 0)->count();

        Remittance::whereNull('user_id')->update(['user_id' => 0]);
        $remCount = Remittance::where('user_id', 0)->count();

        $this->info("Assigned to Admin (user_id=0): {$obCount} obligations, {$recCount} receipts, {$remCount} remittances");

        return Command::SUCCESS;
    }
}
