<?php

return [
    'waiting_list_confirmation_days' => env('S1_WAITING_LIST_CONFIRMATION_DAYS', 30),
    
    'waiting_list_expiry_days' => env('S1_WAITING_LIST_EXPIRY_DAYS', 90),
    
    'session_signup_lock_hours' => env('S1_SESSION_SIGNUP_LOCK_HOURS', 48),
    
    'max_trainees_per_session' => env('S1_MAX_TRAINEES_PER_SESSION', 10),
];