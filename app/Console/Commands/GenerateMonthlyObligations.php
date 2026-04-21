<?php

namespace App\Console\Commands;

use App\Models\Obligation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyObligations extends Command
{
    protected $signature = 'obligations:generate-monthly';

    protected $description = 'Generate monthly obligations automatically on the 15th of each month';

    public function handle(): int
    {
        $nextMonth = Carbon::now()->addMonth();
        $dueDate = $nextMonth->copy()->day(15);

        $exists = Obligation::where('frequency', 'monthly')
            ->where('due_date', $dueDate)
            ->exists();

        if ($exists) {
            $this->info('Monthly obligation already exists for '.$dueDate->format('F Y'));

            return Command::SUCCESS;
        }

        Obligation::create([
            'title' => 'Monthly Payment - '.$dueDate->format('F Y'),
            'amount_expected' => 0,
            'frequency' => 'monthly',
            'due_date' => $dueDate,
            'status' => 'pending',
        ]);

        $this->info('Monthly obligation created for '.$dueDate->format('F Y'));

        return Command::SUCCESS;
    }
}
