<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Moodle Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for VATSIM Germany Moodle integration
    |
    */

    'cache_ttl' => env('MOODLE_CACHE_TTL', 600), // 10 minutes default
    'not_found_cache_ttl' => env('MOODLE_NOT_FOUND_CACHE_TTL', 60), // user without Moodle account / enrolment
];
