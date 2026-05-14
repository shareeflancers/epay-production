<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::call(function () {
    \Illuminate\Support\Facades\Log::info('Cron: Starting queue worker via Artisan::call');
    try {
        \Illuminate\Support\Facades\Artisan::call('queue:work', ['--stop-when-empty' => true]);
        \Illuminate\Support\Facades\Log::info('Cron: Queue worker finished processing.');
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Cron: Queue worker failed: ' . $e->getMessage());
    }
})->everyMinute()
    ->name('queue-worker')
    ->withoutOverlapping();

// Heartbeat log to confirm the scheduler itself is being triggered by cPanel Cron
\Illuminate\Support\Facades\Schedule::call(function () {
    \Illuminate\Support\Facades\Log::debug('Scheduler Heartbeat: Cron job is active.');
})->everyMinute();
