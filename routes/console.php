<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('obligations:generate-monthly')->monthlyOn(15, '00:00');
Schedule::command('obligations:check-due')->dailyAt('09:00');
Schedule::command('reminders:send')->everyMinute()->withoutOverlapping();
