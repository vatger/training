<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function ($schedule) {
        // Daily at 3 AM: Update endorsement activities (0 3 * * *)
        $schedule->command('endorsements:sync-activities', ['--force'])
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Daily at 4 AM: Process endorsement removals (0 4 * * *)
        $schedule->command('endorsements:remove', ['--notify'])
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Daily at 5 AM: Update waiting list activities (0 5 * * *)
        $schedule->command('waitinglist:sync-activity', ['--force'])
            ->dailyAt('05:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Every hour: Check roster status (0 * * * *)
        $schedule->command('roster:check')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        /*
        // Daily at midnight: Clean waiting lists (0 0 * * *)
        $schedule->command('waitinglist:clean')
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->runInBackground();
        */
    })
    ->create();
