<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires the server cron entry from DEPLOY.md: * * * * * php artisan schedule:run
Schedule::command('app:aggregate-page-views')->dailyAt('01:00');
