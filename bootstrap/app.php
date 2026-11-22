<?php

use App\Http\Middleware\AuthenticateApi;
use App\Http\Middleware\CheckCourseAccess;
use App\Http\Middleware\EnsureSandboxAuthEnabled;
use App\Http\Middleware\EnsureUserIsMentor;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Jobs\AutoRemoveGdprRequest;
use App\Jobs\CancelStaleGdprRemoval;
use App\Jobs\SyncTeamRoles;
use App\Jobs\UpdateTeamOverviewPost;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            at: array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16'))),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'mentor' => EnsureUserIsMentor::class,
            'api.auth' => AuthenticateApi::class,
            'course.access' => CheckCourseAccess::class,
            'sandbox.auth' => EnsureSandboxAuthEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('endorsements:remove')
            ->dailyAt('01:00')
            ->withoutOverlapping();

        $schedule->call(fn () => CancelStaleGdprRemoval::dispatchForStale())
            ->name('gdpr-cancel-stale')
            ->dailyAt('01:30');

        $schedule->call(fn () => AutoRemoveGdprRequest::dispatchForDue())
            ->name('gdpr-auto-remove')
            ->dailyAt('01:45');

        $schedule->command('roster:check')
            ->dailyAt('02:00')
            ->withoutOverlapping(120);

        $schedule->command('endorsements:sync-activities')
            ->dailyAt('03:00')
            ->withoutOverlapping(120)
            ->runInBackground();

        $schedule->command('waitinglists:sync-activities')
            ->dailyAt('04:00')
            ->withoutOverlapping(60)
            ->runInBackground();

        $schedule->command('solo:sync-days')
            ->dailyAt('05:00')
            ->withoutOverlapping();

        $schedule->job(new SyncTeamRoles)
            ->dailyAt('05:30')
            ->withoutOverlapping();

        $schedule->job(new UpdateTeamOverviewPost)
            ->dailyAt('06:00')
            ->withoutOverlapping();
    })
    ->create();
