<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Scheduled jobs - run `php artisan schedule:work` locally, or add a cron entry
| `* * * * * php /path/to/artisan schedule:run` in production.
*/

// Expire ended leases (freeing stalls + notifying waitlists) and cancel stale unpaid orders.
Schedule::command('leases:expire')->dailyAt('00:10')->withoutOverlapping()->onOneServer();

// Make sure every layout stall exists and each stall's availability matches its lease.
Schedule::command('stalls:sync')->dailyAt('00:20')->withoutOverlapping()->onOneServer();

// Mark overdue bills with late fees, send reminders and lease-expiry warnings.
Schedule::command('app:analyze-payments')->dailyAt('06:00')->withoutOverlapping()->onOneServer();

// Refresh cached late-payment risk scores for manager dashboards.
Schedule::command('ml:score')->dailyAt('06:30')->withoutOverlapping()->onOneServer();

// Retrain the risk model on the latest payment history.
Schedule::command('ml:train')->weeklyOn(0, '02:00')->withoutOverlapping()->onOneServer();

// Low-stock digest for admins.
Schedule::command('inventory:check-low-stock')->dailyAt('07:00')->onOneServer();

// Keep seven verified daily restore points on the local machine.
Schedule::command('app:backup --keep=7')->dailyAt('01:00')->withoutOverlapping()->onOneServer();

// Records proof that the scheduler is actually running; shown on the admin health page.
Schedule::call(fn () => cache()->put('system.scheduler_last_seen', now()->toIso8601String(), now()->addMinutes(10)))
    ->name('system-health-heartbeat')->everyMinute()->withoutOverlapping();
