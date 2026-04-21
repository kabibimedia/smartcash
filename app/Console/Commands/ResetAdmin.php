<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdmin extends Command
{
    protected $signature = 'admin:reset-password';

    protected $description = 'Reset admin password to default';

    public function handle(): int
    {
        $admin = User::where('email', 'admin@smartcash.com')->first();

        if ($admin) {
            $admin->update(['password' => Hash::make('smartcash123')]);
            $this->info('Admin password reset to: smartcash123');
        } else {
            $this->error('Admin user not found');
        }

        return Command::SUCCESS;
    }
}
