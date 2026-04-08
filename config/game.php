<?php

return [
    'leaderboard' => [
        'size' => (int) env('GAME_LEADERBOARD_SIZE', 15),
    ],

    'session' => [
        'ttl_seconds' => (int) env('GAME_SESSION_TTL_SECONDS', 900),
        'token_length' => (int) env('GAME_SESSION_TOKEN_LENGTH', 64),
        'invalidate_previous_active_sessions' => (bool) env('GAME_INVALIDATE_PREVIOUS_ACTIVE_SESSIONS', false),
        'max_active_sessions_per_user' => env('GAME_MAX_ACTIVE_SESSIONS_PER_USER'),
    ],

    'auth' => [
        'password_min_length' => (int) env('GAME_PASSWORD_MIN_LENGTH', 8),
    ],

    'shop' => [
        'auto_activate_first_skin' => (bool) env('GAME_AUTO_ACTIVATE_FIRST_SKIN', true),
    ],

    'prizes' => [
        'use_remaining_stock' => (bool) env('GAME_PRIZE_USE_REMAINING_STOCK', true),
    ],

    'score_validation' => [
        'min_score' => (int) env('GAME_MIN_SCORE', 0),
        'max_score' => (int) env('GAME_MAX_SCORE', 1000000),
        'max_coins_collected_per_run' => (int) env('GAME_MAX_COINS_COLLECTED_PER_RUN', 1000),
        'min_duration_seconds' => (int) env('GAME_MIN_DURATION_SECONDS', 5),
        'max_duration_seconds' => (int) env('GAME_MAX_DURATION_SECONDS', 7200),
    ],

    'rate_limits' => [
        'login_per_minute' => (int) env('GAME_RATE_LIMIT_LOGIN_PER_MINUTE', 5),
        'register_per_minute' => (int) env('GAME_RATE_LIMIT_REGISTER_PER_MINUTE', 3),
        'country_check_per_minute' => (int) env('GAME_RATE_LIMIT_COUNTRY_CHECK_PER_MINUTE', 60),
        'mvp_settings_per_minute' => (int) env('GAME_RATE_LIMIT_MVP_SETTINGS_PER_MINUTE', 60),
        'profile_per_minute' => (int) env('GAME_RATE_LIMIT_PROFILE_PER_MINUTE', 60),
        'active_skin_per_minute' => (int) env('GAME_RATE_LIMIT_ACTIVE_SKIN_PER_MINUTE', 20),
        'session_start_per_minute' => (int) env('GAME_RATE_LIMIT_SESSION_START_PER_MINUTE', 30),
        'submit_score_per_minute' => (int) env('GAME_RATE_LIMIT_SUBMIT_SCORE_PER_MINUTE', 20),
        'buy_skin_per_minute' => (int) env('GAME_RATE_LIMIT_BUY_SKIN_PER_MINUTE', 10),
    ],
];
