<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'vateud' => [
        'token' => env('VATEUD_TOKEN'),
        'use_mock' => env('VATEUD_USE_MOCK', false),
        'min_activity_minutes' => (int) env('VATEUD_MIN_ACTIVITY_MINUTES', 180),
        'removal_warning_days' => (int) env('VATEUD_REMOVAL_WARNING_DAYS', 31),
        'min_endorsement_age_days' => (int) env('VATEUD_MIN_ENDORSEMENT_AGE_DAYS', 180),
        'atd_lead_cid' => env('VATEUD_ATD_LEAD_CID', 1441619),
    ],

    'training' => [
        'min_activity' => env('TRAINING_MIN_ACTIVITY', 10),
        'display_activity' => env('TRAINING_DISPLAY_ACTIVITY', 8),

        // Minimum hours required for rating courses
        'min_hours' => env('TRAINING_MIN_HOURS', 25),

        // S3 rating change restriction (days)
        'rating_change_days' => env('TRAINING_RATING_CHANGE_DAYS', 90),

        'roster_inactivity_warning_days' => env('ROSTER_INACTIVITY_WARNING_DAYS', 330), // 11 months
        'roster_removal_grace_days' => env('ROSTER_REMOVAL_GRACE_DAYS', 35),
        'roster_max_inactivity_days' => env('ROSTER_MAX_INACTIVITY_DAYS', 366), // 1 year + 1 day
    ],

    'vatger' => [
        'api_key' => env('VATGER_API_KEY'),
        'api_url' => env('VATGER_API_URL', 'http://vatsim-germany.org/api'),

        'oauth_client_id' => env('VATGER_OAUTH_CLIENT_ID'),
        'oauth_client_secret' => env('VATGER_OAUTH_CLIENT_SECRET'),
        'oauth_redirect_uri' => env('VATGER_OAUTH_REDIRECT_URI'),
        'oauth_auth_url' => env('VATGER_OAUTH_AUTH_URL'),
        'oauth_token_url' => env('VATGER_OAUTH_TOKEN_URL'),
        'oauth_base_url' => env('VATGER_OAUTH_BASE_URL'),

        // VATSIM Connect sandbox (https://vatsim.dev/services/connect/sandbox/) — dev-only
        // login that replaces the old admin backdoor. Gated by App\Support\SandboxAuth, which
        // requires a non-production environment AND a matching allowed host, so this never
        // activates in production even if APP_ENV is misconfigured.
        'oauth_sandbox_client_id' => env('VATSIM_SANDBOX_CLIENT_ID'),
        'oauth_sandbox_client_secret' => env('VATSIM_SANDBOX_CLIENT_SECRET'),
        'oauth_sandbox_redirect_uri' => env('VATSIM_SANDBOX_REDIRECT_URI'),
        'oauth_sandbox_auth_url' => env('VATSIM_SANDBOX_AUTH_URL', 'https://auth-dev.vatsim.net/oauth/authorize'),
        'oauth_sandbox_token_url' => env('VATSIM_SANDBOX_TOKEN_URL', 'https://auth-dev.vatsim.net/oauth/token'),
        'oauth_sandbox_base_url' => env('VATSIM_SANDBOX_BASE_URL', 'https://auth-dev.vatsim.net/api'),

        // Comma-separated hostname patterns (Str::is wildcards allowed) permitted to use the
        // sandbox login. Acts as the second, independent gate alongside the environment check.
        'sandbox_allowed_hosts' => env('VATSIM_SANDBOX_ALLOWED_HOSTS', 'localhost,127.0.0.1,*.test,*.localhost'),
    ],
];
