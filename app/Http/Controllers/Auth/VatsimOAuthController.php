<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\VatsimConnectService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class VatsimOAuthController extends Controller
{
    protected $vatsimConnect;

    public function __construct(VatsimConnectService $vatsimConnect)
    {
        $this->vatsimConnect = $vatsimConnect;
    }

    /**
     * Redirect to VATSIM Connect OAuth
     */
    public function redirect(): RedirectResponse
    {
        try {
            $authUrl = $this->vatsimConnect->getAuthorizationUrl();
            return redirect()->away($authUrl);
        } catch (\Exception $e) {
            Log::error('Failed to generate OAuth URL: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'oauth' => 'Failed to connect to VATSIM. Please try again.'
            ]);
        }
    }

    /**
     * Handle the OAuth callback from VATSIM Connect
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $code = $request->input('code');
            $state = $request->input('state');
            $error = $request->input('error');

            if ($error) {
                Log::warning('OAuth error from VATSIM Connect: ' . $error);
                return redirect()->route('login')->withErrors([
                    'oauth' => 'Authorization was denied or failed. Please try again.'
                ]);
            }

            if (!$code) {
                return redirect()->route('login')->withErrors([
                    'oauth' => 'Authorization code not received. Please try again.'
                ]);
            }

            // Prevent code reuse
            $cacheKey = 'oauth_code_processed_' . hash('sha256', $code);
            if (Cache::has($cacheKey)) {
                return redirect()->route('login')->withErrors([
                    'oauth' => 'This authorization code has already been used.'
                ]);
            }
            Cache::put($cacheKey, true, 600);

            try {
                // Get access token from VATSIM Connect
                $tokenData = $this->vatsimConnect->getAccessToken($code, $state);
                Log::info('Access token obtained successfully');

                // Get user profile from VATSIM Connect
                $profile = $this->vatsimConnect->getUserProfile($tokenData['access_token']);
                Log::info('User profile obtained', ['vatsim_id' => $profile['id']]);

                // Create or update user
                $user = $this->createOrUpdateUser($profile);
                Log::info('User created/updated', ['user_id' => $user->id]);

                // Assign roles
                $this->assignRoles($user, $profile['teams'] ?? []);
                Log::info('Roles assigned');

                // Log the user in
                Auth::login($user, true); // true for remember me

                // Regenerate session for security
                $request->session()->regenerate();

                return redirect()->intended(route('dashboard', absolute: false));

            } catch (\Exception $e) {
                Cache::forget($cacheKey);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('OAuth callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('login')->withErrors([
                'oauth' => 'Authentication failed. Please try again.'
            ]);
        }
    }

    /**
     * Create or update user from VATSIM Connect profile
     */
    protected function createOrUpdateUser(array $profile): User
    {
        $mentorGroups = ['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor'];
        $teams = $profile['teams'] ?? [];
        
        $isStaff = !empty(array_intersect($mentorGroups, $teams)) ||
                   in_array('ATD Leitung', $teams) ||
                   in_array('VATGER Leitung', $teams);
        
        $isSuperuser = in_array('ATD Leitung', $teams) || 
                       in_array('VATGER Leitung', $teams);

        $lastRatingChange = null;
        if (!empty($profile['last_rating_change_at'])) {
            try {
                $lastRatingChange = Carbon::createFromFormat('Y-m-d H:i:s', $profile['last_rating_change_at']);
            } catch (\Exception $e) {
                $lastRatingChange = Carbon::createFromTimestamp(0);
            }
        } else {
            $lastRatingChange = Carbon::createFromTimestamp(0);
        }

        return User::updateOrCreate(
            ['vatsim_id' => $profile['id']],
            [
                'first_name' => $profile['firstname'],
                'last_name' => $profile['lastname'],
                'email' => $profile['email'] ?? null,
                'rating' => $profile['rating_atc'],
                'subdivision' => $profile['subdivision_code'] ?? null,
                'last_rating_change' => $lastRatingChange,
                'is_staff' => $isStaff,
                'is_superuser' => $isSuperuser,
                'email_verified_at' => now(), // Consider email verified from VATSIM
            ]
        );
    }

    /**
     * Assign roles to user based on teams
     */
    protected function assignRoles(User $user, array $teams): void
    {
        // Remove all existing roles first
        $user->roles()->detach();
        
        // Assign roles based on teams
        foreach ($teams as $team) {
            $role = Role::where('name', $team)->first();
            if ($role) {
                $user->roles()->attach($role->id);
            }
        }
    }
}