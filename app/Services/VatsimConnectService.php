<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class VatsimConnectService
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $authUrl;
    protected $tokenUrl;
    protected $apiBaseUrl;
    protected $mentorGroups = ['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor'];

    public function __construct()
    {
        $this->clientId = config('services.vatger.oauth_client_id');
        $this->clientSecret = config('services.vatger.oauth_client_secret');
        $this->redirectUri = config('services.vatger.oauth_redirect_uri');
        $this->authUrl = config('services.vatger.oauth_auth_url');
        $this->tokenUrl = config('services.vatger.oauth_token_url');
        $this->apiBaseUrl = config('services.vatger.oauth_base_url');
    }

    public function getAuthorizationUrl(): string
    {
        $state = Str::random(40);
        
        Cache::put('oauth_state_' . $state, true, now()->addMinutes(10));

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'name rating assignment teams',
            'state' => $state,
        ];

        return $this->authUrl . '?' . http_build_query($params);
    }

    public function getAccessToken(string $code, ?string $state = null): array
    {
        if ($state && !Cache::get('oauth_state_' . $state)) {
            throw new \Exception('Invalid OAuth state parameter');
        }

        if ($state) {
            Cache::forget('oauth_state_' . $state);
        }

        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to obtain access token: ' . $response->body());
        }

        return $response->json();
    }

    public function getUserProfile(string $accessToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
        ])->get($this->apiBaseUrl . '/userinfo');

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch user profile: ' . $response->body());
        }

        $profile = $response->json();

        Log::info('VATGER OAuth response', ['response' => $profile]);

        if (!isset($profile['id'])) {
            throw new \Exception('Could not extract user ID from response');
        }

        return $profile;
    }

    public function syncUserFromProfile(array $profile): User
    {
        $teams = $profile['teams'] ?? [];

        $isMentor = count(array_intersect($this->mentorGroups, $teams)) > 0;
        $isLeadership = in_array('ATD Leitung', $teams) || in_array('VATGER Leitung', $teams);

        $isStaff = $isMentor || $isLeadership;
        $isSuperuser = $isLeadership;

        $user = User::updateOrCreate(
            ['vatsim_id' => $profile['id']],
            [
                'first_name' => $profile['firstname'],
                'last_name' => $profile['lastname'],
                'rating' => $profile['rating_atc'] ?? 1,
                'subdivision' => $profile['subdivision_code'] ?? null,
                'last_rating_change' => $profile['last_rating_change_at']
                    ? \Carbon\Carbon::parse($profile['last_rating_change_at'])
                    : null,
                'is_staff' => $isStaff,
                'is_superuser' => $isSuperuser,
            ]
        );

        $this->syncUserRoles($user, $teams);

        return $user;
    }

    protected function syncUserRoles(User $user, array $teams): void
    {
        $rolesToSync = [];

        foreach ($this->mentorGroups as $mentorGroup) {
            if (in_array($mentorGroup, $teams)) {
                $role = Role::firstOrCreate(attributes: ['name' => $mentorGroup]);
                $rolesToSync[] = $role->id;
            }
        }

        if (in_array('ATD Leitung', $teams)) {
            $role = Role::firstOrCreate(['name' => 'ATD Leitung']);
            $rolesToSync[] = $role->id;
        }

        if (in_array('VATGER Leitung', $teams)) {
            $role = Role::firstOrCreate(['name' => 'VATGER Leitung']);
            $rolesToSync[] = $role->id;
        }

        $user->roles()->sync($rolesToSync);
    }
}