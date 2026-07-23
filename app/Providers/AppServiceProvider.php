<?php

namespace App\Providers;

use App\Integrations\Moodle\FakeMoodleClient;
use App\Integrations\Moodle\MoodleClient;
use App\Integrations\Moodle\MoodleClientInterface;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Integrations\Vatger\FakeVatgerClient;
use App\Integrations\Vatger\VatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\TrainingLog;
use App\Policies\EndorsementPolicy;
use App\Policies\TrainingLogPolicy;
use App\Services\VatsimConnectService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register VATSIM Connect Service
        $this->app->singleton(VatsimConnectService::class, function ($app) {
            return new VatsimConnectService;
        });

        $fake = $this->app->environment('testing', 'local');

        $this->app->bind(
            VatEudClientInterface::class,
            $fake
            ? FakeVatEudClient::class
            : VatEudClient::class,
        );

        $this->app->bind(
            VatgerClientInterface::class,
            $fake
            ? FakeVatgerClient::class
            : VatgerClient::class,
        );

        $this->app->bind(
            MoodleClientInterface::class,
            $fake
            ? FakeMoodleClient::class
            : MoodleClient::class,
        );

        $this->app->bind(
            VatEudClientInterface::class,
            $fake
            ? FakeVatEudClient::class
            : VatEudClient::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.forcehttps')) {
            URL::forceScheme('https');
        }

        date_default_timezone_set(config('app.timezone', 'UTC'));

        Gate::define('mentor', [EndorsementPolicy::class, 'mentor']);
        Gate::define('endorsements.manage', [EndorsementPolicy::class, 'viewManagement']);
        Gate::define('endorsements.remove-tier1', [EndorsementPolicy::class, 'removeTier1']);
        Gate::define('endorsements.request-tier2', [EndorsementPolicy::class, 'requestTier2']);
        Gate::define('endorsements.view-own', [EndorsementPolicy::class, 'viewOwn']);

        Gate::policy(TrainingLog::class, TrainingLogPolicy::class);
    }
}
