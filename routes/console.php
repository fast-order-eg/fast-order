<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic subscription expiration check every 30 minutes
Schedule::command('subscriptions:check-expired')->everyThirtyMinutes();

// Schedule abandoned carts tracking every 30 minutes
Schedule::command('carts:track-abandoned')->everyThirtyMinutes();

// Schedule abandoned carts sync with orders every 30 minutes
Schedule::command('carts:sync-orders')->everyThirtyMinutes();
