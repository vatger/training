<?php

namespace App\Providers;

use App\Services\VatsimConnectService;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isProduction()) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Gate::define('mentor', [EndorsementPolicy::class, 'mentor']);
        Gate::define('endorsements.manage', [EndorsementPolicy::class, 'viewManagement']);
        Gate::define('endorsements.remove-tier1', [EndorsementPolicy::class, 'removeTier1']);
        Gate::define('endorsements.request-tier2', [EndorsementPolicy::class, 'requestTier2']);
        Gate::define('endorsements.view-own', [EndorsementPolicy::class, 'viewOwn']);

    }
}