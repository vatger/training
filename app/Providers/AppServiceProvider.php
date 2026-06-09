<?php

namespace App\Providers;

use App\Services\VatsimConnectService;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use Illuminate\Support\ServiceProvider;
use App\Policies\EndorsementPolicy;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register VATSIM Connect Service
        $this->app->singleton(VatsimConnectService::class, function ($app) {
            return new VatsimConnectService();
        });

        $this->app->bind(
            \App\Integrations\Vatger\VatgerClientInterface::class,
            app()->environment('testing', 'local')
            ? \App\Integrations\Vatger\FakeVatgerClient::class
            : \App\Integrations\Vatger\VatgerClient::class,
        );

        $this->app->bind(
            VatEudClientInterface::class,
            $this->app->environment('testing', 'local')
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
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        date_default_timezone_set(config('app.timezone', 'UTC'));

        Gate::define('mentor', [EndorsementPolicy::class, 'mentor']);
        Gate::define('endorsements.manage', [EndorsementPolicy::class, 'viewManagement']);
        Gate::define('endorsements.remove-tier1', [EndorsementPolicy::class, 'removeTier1']);
        Gate::define('endorsements.request-tier2', [EndorsementPolicy::class, 'requestTier2']);
        Gate::define('endorsements.view-own', [EndorsementPolicy::class, 'viewOwn']);
    }
}