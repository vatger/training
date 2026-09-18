<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VatsimConnectService
{
    protected $clientId;

    protected $clientSecret;

    protected $redirectUri;

    protected $authUrl;

    protected $tokenUrl;

    protected $apiBaseUrl;

    protected bool $sandbox;

    protected $mentorGroups = ['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor'];

    /**
     * @param  bool  $sandbox  Talk to the VATSIM Connect sandbox (auth-dev.vatsim.net) instead
     *                         of the production VATGER OAuth proxy. Callers must gate this with
     *                         App\Support\SandboxAuth — this service performs no environment
     *                         checks of its own.
     */
    public function __construct(bool $sandbox = false)
    {
        $this->sandbox = $sandbox;
        $prefix = $sandbox ? 'oauth_sandbox_' : 'oauth_';

        $this->clientId = config("services.vatger.{$prefix}client_id");
        $this->clientSecret = config("services.vatger.{$prefix}client_secret");
        $this->redirectUri = config("services.vatger.{$prefix}redirect_uri");
        $this->authUrl = config("services.vatger.{$prefix}auth_url");
        $this->tokenUrl = config("services.vatger.{$prefix}token_url");
        $this->apiBaseUrl = config("services.vatger.{$prefix}base_url");
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => $this->sandbox ? 'full_name email vatsim_details' : 'name rating assignment teams',
            'state' => $state,
        ];

        return $this->authUrl.'?'.http_build_query($params);
    }

    public function getAccessToken(string $code): array
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to obtain access token: '.$response->body());
        }

        return $response->json();
    }

    public function getUserProfile(string $accessToken): array
    {
        $path = $this->sandbox ? '/user' : '/userinfo';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Accept' => 'application/json',
        ])->get($this->apiBaseUrl.$path);

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch user profile: '.$response->body());
        }

        $profile = $response->json();

        /* Log::info('VATGER OAuth response', ['response' => $profile]); */

        if ($this->sandbox) {
            $profile = $this->normalizeSandboxProfile($profile);
        }

        if (! isset($profile['id'])) {
            throw new \Exception('Could not extract user ID from response');
        }

        return $profile;
    }

    /**
     * Map the raw VATSIM Connect API response (nested under `data`, see
     * https://vatsim.dev/api/connect-api/get-user) onto the flat profile shape the
     * VATGER OAuth proxy returns, which the rest of the app expects. The sandbox has no
     * concept of VATGER "teams", so that key is always empty here.
     */
    protected function normalizeSandboxProfile(array $response): array
    {
        $data = $response['data'] ?? [];

        return [
            'id' => $data['cid'] ?? null,
            'firstname' => $data['personal']['name_first'] ?? null,
            'lastname' => $data['personal']['name_last'] ?? null,
            'email' => $data['personal']['email'] ?? null,
            'rating_atc' => $data['vatsim']['rating']['id'] ?? null,
            'subdivision_code' => $data['vatsim']['subdivision']['id'] ?? null,
            'last_rating_change_at' => null,
            'teams' => [],
        ];
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
                    ? Carbon::parse($profile['last_rating_change_at'])
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
