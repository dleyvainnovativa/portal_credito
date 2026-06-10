<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduling
|--------------------------------------------------------------------------
| Laravel 11+ defines scheduled tasks here. The purge runs hourly to reclaim
| temp files from abandoned onboarding sessions.
|
| On Hostinger (shared hosting), add ONE cron entry in the control panel so
| the scheduler ticks every minute:
|
|   * * * * * cd /home/USER/domains/SITE/public_html && php artisan schedule:run >> /dev/null 2>&1
|
| Laravel then fires uploads:purge on its hourly cadence from that single tick.
*/

Schedule::command('uploads:purge')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
