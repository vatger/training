<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\VatsimConnectService;
use App\Support\SandboxAuth;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VatsimOAuthController extends Controller
{
    protected $vatsimConnect;

    public function __construct(VatsimConnectService $vatsimConnect)
    {
        $this->vatsimConnect = $vatsimConnect;
    }

    public function redirect(): RedirectResponse
    {
        return $this->handleRedirect($this->vatsimConnect, 'oauth_state');
    }

    public function callback(Request $request): RedirectResponse
    {
        return $this->handleCallback($request, $this->vatsimConnect, 'oauth_state', sandbox: false);
    }

    /**
     * Dev-only: redirect to the VATSIM Connect sandbox instead of production VATSIM Connect.
     * Also re-checked here (not just via the `sandbox.auth` route middleware) so this can
     * never fire in production even if the route/middleware wiring is ever changed.
     */
    public function sandboxRedirect(Request $request): RedirectResponse
    {
        abort_unless(SandboxAuth::enabled($request), 404);

        return $this->handleRedirect(new VatsimConnectService(sandbox: true), 'oauth_sandbox_state');
    }

    public function sandboxCallback(Request $request): RedirectResponse
    {
        abort_unless(SandboxAuth::enabled($request), 404);

        return $this->handleCallback($request, new VatsimConnectService(sandbox: true), 'oauth_sandbox_state', sandbox: true);
    }

    protected function handleRedirect(VatsimConnectService $connect, string $stateKey): RedirectResponse
    {
        try {
            $state = Str::random(40);
            session([$stateKey => $state]);

            $authUrl = $connect->getAuthorizationUrl($state);

            return redirect()->away($authUrl);
        } catch (\Exception $e) {
            Log::error('Failed to generate OAuth URL: '.$e->getMessage());

            return redirect()->route('login')->withErrors([
                'oauth' => 'Failed to connect to VATSIM. Please try again.',
            ]);
        }
    }

    protected function handleCallback(Request $request, VatsimConnectService $connect, string $stateKey, bool $sandbox): RedirectResponse
    {
        try {
            $code = $request->input('code');
            $state = $request->input('state');
            $error = $request->input('error');

            if ($error) {
                Log::warning('OAuth error from VATSIM Connect: '.$error);

                return redirect()->route('login')->withErrors([
                    'oauth' => 'Authorization was denied or failed. Please try again.',
                ]);
            }

            if (! $code) {
                return redirect()->route('login')->withErrors([
                    'oauth' => 'Authorization code not received. Please try again.',
                ]);
            }

            $expectedState = session()->pull($stateKey);
            if (! $state || ! $expectedState || ! hash_equals($expectedState, $state)) {
                return redirect()->route('login')->withErrors([
                    'oauth' => 'Invalid OAuth state. Please try again.',
                ]);
            }

            $cacheKey = 'oauth_code_processed_'.hash('sha256', $code);
            if (Cache::has($cacheKey)) {
                return redirect()->route('login')->withErrors([
                    'oauth' => 'This authorization code has already been used.',
                ]);
            }
            Cache::put($cacheKey, true, 600);

            try {
                $tokenData = $connect->getAccessToken($code);
                $profile = $connect->getUserProfile($tokenData['access_token']);

                $user = $this->createOrUpdateUser($profile);
                $this->assignRoles($user, $profile['teams'] ?? []);

                if ($sandbox) {
                    // Sandbox accounts carry no VATGER "teams", so grant full staff/superuser
                    // access directly — this is the dev-only replacement for the old
                    // app:create-admin bootstrap, gated entirely by SandboxAuth::enabled().
                    $user->forceFill([
                        'is_staff' => true,
                        'is_superuser' => true,
                    ])->save();
                }

                Auth::login($user, true);
                $request->session()->regenerate();

                return redirect()->intended(route('dashboard', absolute: false));

            } catch (\Exception $e) {
                Cache::forget($cacheKey);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('OAuth callback error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->withErrors([
                'oauth' => 'Authentication failed. Please try again.',
            ]);
        }
    }

    protected function createOrUpdateUser(array $profile): User
    {
        $mentorGroups = ['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor'];
        $teams = $profile['teams'] ?? [];

        $isStaff = ! empty(array_intersect($mentorGroups, $teams)) ||
                   in_array('ATD Leitung', $teams) ||
                   in_array('VATGER Leitung', $teams);

        $isSuperuser = in_array('ATD Leitung', $teams) ||
                       in_array('VATGER Leitung', $teams);

        $lastRatingChange = null;
        if (! empty($profile['last_rating_change_at'])) {
            try {
                $lastRatingChange = Carbon::createFromFormat('Y-m-d H:i:s', $profile['last_rating_change_at']);
            } catch (\Exception $e) {
                $lastRatingChange = null;
            }
        }

        $user = User::firstOrNew(['vatsim_id' => $profile['id']]);

        $newRating = $profile['rating_atc'];
        $previousRating = $user->last_known_rating ?? $user->rating;

        if ($user->exists && $previousRating !== null && $newRating > $previousRating) {
            $user->rating_upgraded_at = now();
            $user->rating_upgrade_pending = false;
        }

        $user->fill([
            'first_name' => $profile['firstname'],
            'last_name' => $profile['lastname'],
            'email' => $profile['email'] ?? null,
            'rating' => $newRating,
            'last_known_rating' => $previousRating,
            'subdivision' => $profile['subdivision_code'] ?? null,
            'last_rating_change' => $lastRatingChange,
            'is_staff' => $isStaff,
            'is_superuser' => $isSuperuser,
            'email_verified_at' => now(),
        ]);

        $user->save();

        return $user;
    }

    protected function assignRoles(User $user, array $teams): void
    {
        $user->roles()->detach();

        foreach ($teams as $team) {
            $role = Role::where('name', $team)->first();
            if ($role) {
                $user->roles()->attach($role->id);
            }
        }
    }
}
