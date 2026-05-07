<?php

use App\Console\Commands\ProcessScheduledSimulados;
use App\Console\Commands\PurgeSimulationSessions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(PurgeSimulationSessions::class)->hourly();
Schedule::command(ProcessScheduledSimulados::class)->everyMinute();
