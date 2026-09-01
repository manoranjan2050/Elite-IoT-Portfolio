<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily database backup at 02:30, keep 14 days.
// Needs the scheduler cron: * * * * * php /path/to/artisan schedule:run
Schedule::command('kavach:backup --keep=14')->dailyAt('02:30');
