<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'vatsim_id' => $request->user()->vatsim_id,
                    'name' => $request->user()->name ?? 'Admin User',
                    'first_name' => $request->user()->first_name,
                    'last_name' => $request->user()->last_name,
                    'email' => $request->user()->email,
                    'rating' => $request->user()->rating,
                    'subdivision' => $request->user()->subdivision,
                    'is_staff' => $request->user()->is_staff,
                    'is_superuser' => $request->user()->is_superuser,
                    'is_admin' => $request->user()->is_admin ?? false,
                    'is_vatsim_user' => $request->user()->isVatsimUser(),
                    'roles' => $request->user()->isVatsimUser() ? $request->user()->roles->pluck('name') : [],
                    'is_mentor' => $request->user()->isVatsimUser() ? $request->user()->isMentor() : false,
                    'is_leadership' => $request->user()->isVatsimUser() ? $request->user()->isLeadership() : false,
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}