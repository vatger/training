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
use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\Course;
use App\Models\Cpt;
use App\Models\EndorsementActivity;
use App\Models\Examiner;
use App\Models\Familiarisation;
use App\Models\FamiliarisationSector;
use App\Models\GdprRemovalRequest;
use App\Models\GdprReviewItem;
use App\Models\LeadingMentor;
use App\Models\Role;
use App\Models\Subfamiliarisation;
use App\Models\SubfamiliarisationSession;
use App\Models\Tier2Endorsement;
use App\Models\TrainingLog;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Models\WaitingListRestriction;
use App\Policies\Admin\ActivityLogPolicy;
use App\Policies\Admin\ApiKeyPolicy;
use App\Policies\Admin\CoursePolicy;
use App\Policies\Admin\CptPolicy;
use App\Policies\Admin\EndorsementActivityPolicy;
use App\Policies\Admin\ExaminerPolicy;
use App\Policies\Admin\FamiliarisationPolicy;
use App\Policies\Admin\FamiliarisationSectorPolicy;
use App\Policies\Admin\GdprRemovalRequestPolicy;
use App\Policies\Admin\GdprReviewItemPolicy;
use App\Policies\Admin\LeadingMentorPolicy;
use App\Policies\Admin\RolePolicy;
use App\Policies\Admin\SubfamiliarisationPolicy;
use App\Policies\Admin\SubfamiliarisationSessionPolicy;
use App\Policies\Admin\Tier2EndorsementPolicy;
use App\Policies\Admin\UserPolicy;
use App\Policies\Admin\WaitingListEntryPolicy;
use App\Policies\Admin\WaitingListRestrictionPolicy;
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

        // Policies backing the admin panel resources live in their own folder, so they are not auto-discovered.
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
        Gate::policy(ApiKey::class, ApiKeyPolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Cpt::class, CptPolicy::class);
        Gate::policy(EndorsementActivity::class, EndorsementActivityPolicy::class);
        Gate::policy(Examiner::class, ExaminerPolicy::class);
        Gate::policy(Familiarisation::class, FamiliarisationPolicy::class);
        Gate::policy(FamiliarisationSector::class, FamiliarisationSectorPolicy::class);
        Gate::policy(GdprRemovalRequest::class, GdprRemovalRequestPolicy::class);
        Gate::policy(GdprReviewItem::class, GdprReviewItemPolicy::class);
        Gate::policy(LeadingMentor::class, LeadingMentorPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Subfamiliarisation::class, SubfamiliarisationPolicy::class);
        Gate::policy(SubfamiliarisationSession::class, SubfamiliarisationSessionPolicy::class);
        Gate::policy(Tier2Endorsement::class, Tier2EndorsementPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(WaitingListEntry::class, WaitingListEntryPolicy::class);
        Gate::policy(WaitingListRestriction::class, WaitingListRestrictionPolicy::class);
    }
}
