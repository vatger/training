<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Controller Activity Engine
    |--------------------------------------------------------------------------
    |
    | Configuration for the activity engine that calculates controlled minutes
    | for every VATSIM Germany login station from VATGLASSES sector ownership
    | data and the datahub station registry. See app/Domain/Activity.
    |
    */

    // Rolling window (in days) over which activity is accumulated.
    'window_days' => (int) env('ACTIVITY_WINDOW_DAYS', 180),

    // Minimum counted minutes for a position to be considered "active".
    // Shared with the endorsement retention flow.
    'min_minutes' => (int) env('VATEUD_MIN_ACTIVITY_MINUTES', 180),

    'coverage' => [
        // When a station owns several primary sectors at once, count the
        // wall-clock time during which the controller owned >= 1 of them
        // (true, default) rather than summing per-sector minutes (false).
        'union_minutes' => (bool) env('ACTIVITY_COVERAGE_UNION', true),
    ],

    // Queue used by every activity job.
    'queue' => env('ACTIVITY_QUEUE', 'activity'),

    // Number of CIDs per RecalculateControllerActivity batch job.
    'batch_size' => (int) env('ACTIVITY_BATCH_SIZE', 25),

    // 'log' records unresolved callsigns and continues; 'throw' fails loudly.
    'unresolved_action' => env('ACTIVITY_UNRESOLVED_ACTION', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Reference data sources (mirrored to storage/app/activity)
    |--------------------------------------------------------------------------
    */
    'sources' => [
        'vatglasses' => [
            'raw_base' => env(
                'ACTIVITY_VATGLASSES_RAW_BASE',
                'https://raw.githubusercontent.com/VATGER-Nav/vatglasses-data/main/data',
            ),
            // Primary dataset first; any additional files are merged in for
            // cross-dataset "country/uid" owner references.
            'files' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('ACTIVITY_VATGLASSES_FILES', 'ed.json')),
            ))),
        ],

        'datahub' => [
            'raw_base' => env(
                'ACTIVITY_DATAHUB_RAW_BASE',
                'https://raw.githubusercontent.com/VATGER-Nav/datahub/refs/heads/production/api',
            ),
            'firs' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('ACTIVITY_DATAHUB_FIRS', 'edgg,edmm,edww,eduu,edyy')),
            ))),
            'types' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('ACTIVITY_DATAHUB_TYPES', 'ctr,app,twr,gnd,del,afis')),
            ))),
            // Combined station list, used as a fallback / cross-check.
            'stations_file' => env('ACTIVITY_DATAHUB_STATIONS_FILE', 'stations.json'),
        ],

        'sessions' => [
            'base_url' => env('ACTIVITY_SESSIONS_BASE_URL', 'https://stats.vatsim-germany.org/api'),
            'timeout' => (int) env('ACTIVITY_SESSIONS_TIMEOUT', 25),
            'retries' => (int) env('ACTIVITY_SESSIONS_RETRIES', 2),
            'request_delay_ms' => (int) env('ACTIVITY_SESSIONS_REQUEST_DELAY_MS', 0),

            // GET /api/atc/sessions keyset pagination (max 5000 server-side).
            'per_page' => (int) env('ACTIVITY_SESSIONS_PER_PAGE', 5000),
            'max_pages' => (int) env('ACTIVITY_SESSIONS_MAX_PAGES', 2000),

            // The per-CID endpoint returns a single uncursored page; walk it in windows.
            'max_window_days' => (int) env('ACTIVITY_SESSIONS_MAX_WINDOW_DAYS', 90),

            // Only pull callsigns starting with one of these (server-side filter,
            // comma form). Empty string = no filter.
            'callsign_prefixes' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('ACTIVITY_SESSIONS_CALLSIGN_PREFIXES', 'ED,ET')),
            ))),

            // Earliest date the stats archive holds sessions; range fetches are
            // clamped to this so we don't request empty history.
            'history_since' => env('ACTIVITY_SESSIONS_HISTORY_SINCE', '2025-10-12'),

            // Days back from "now" that may still be mutated upstream (~15 min
            // window on live rows) and must not be cached long.
            'volatile_days' => (int) env('ACTIVITY_SESSIONS_VOLATILE_DAYS', 2),
        ],
    ],

    // Storage disk + directory the raw mirror is written to.
    'mirror' => [
        'disk' => env('ACTIVITY_MIRROR_DISK', 'local'),
        'path' => env('ACTIVITY_MIRROR_PATH', 'activity'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Callsign aliases / overrides
    |--------------------------------------------------------------------------
    |
    | Highest-priority step of the callsign resolver. Maps an observed logon
    | callsign (exact, upper-case) to a canonical datahub logon. Use for
    | legacy renames and dynamic bandbox logins that cannot be derived.
    | An entry may be a string (canonical logon) or an array with an optional
    | "until" ISO date after which the alias stops applying.
    |
    */
    'callsign_aliases' => [
        'EDWW_CTR' => 'EDWW_W_CTR',
    ],
];
