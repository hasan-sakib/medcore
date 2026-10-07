<?php

use App\Jobs\ExpireMedicineBatchesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled tasks
Schedule::command('queue:prune-failed --hours=168')->weekly();

// Phase 3: expire out-of-date batches and log near-expiry / low-stock alerts per tenant
Schedule::job(new ExpireMedicineBatchesJob)->dailyAt('01:00')->onOneServer();
