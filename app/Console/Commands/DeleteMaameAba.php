<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeleteMaameAba extends Command
{
    protected $signature = 'delete:maameaba';

    protected $description = 'Delete Maame Aba user';

    public function handle(): int
    {
        $user = User::where('email', 'maameaba712@gmail.com')->first();

        if ($user) {
            $user->delete();
            $this->info('Maame Aba deleted.');
        } else {
            $this->info('Maame Aba not found.');
        }

        return Command::SUCCESS;
    }
}
