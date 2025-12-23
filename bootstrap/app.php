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

        $middleware->alias([
            'mentor' => \App\Http\Middleware\EnsureUserIsMentor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('endorsements:sync-activities')
            ->dailyAt('03:00')
            ->withoutOverlapping(120)
            ->runInBackground();

        $schedule->command('waitinglists:sync-activities')
            ->dailyAt('04:00')
            ->withoutOverlapping(60)
            ->runInBackground();

        $schedule->command('endorsements:remove')
            ->dailyAt('08:00')
            ->withoutOverlapping();

        $schedule->command('roster:check')
            ->dailyAt('02:00')
            ->withoutOverlapping(120);
    })
    ->create();
