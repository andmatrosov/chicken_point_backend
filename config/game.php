<?php

return [
    'leaderboard' => [
        'size' => (int) env('GAME_LEADERBOARD_SIZE', 15),
    ],

    'session' => [
        'token_length' => (int) env('GAME_SESSION_TOKEN_LENGTH', 64),
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

    'anti_cheat' => [
        'mode' => env('GAME_ANTICHEAT_MODE', env('GAME_SCORE_VELOCITY_MODE', 'flag')),
        'soft_score_velocity_threshold' => (float) env('GAME_SOFT_SCORE_VELOCITY_THRESHOLD', env('GAME_MAX_SCORE_PER_SECOND', 4.0)),
        'soft_score_minimum' => (int) env('GAME_SOFT_SCORE_MINIMUM', 50),
        'suspicion_points_to_flag' => (int) env('GAME_SUSPICION_POINTS_TO_FLAG', 3),
        'duration_mismatch_enabled' => filter_var(env('GAME_DURATION_MISMATCH_ENABLED', true), FILTER_VALIDATE_BOOL),
        'duration_mismatch_grace_seconds' => (int) env('GAME_DURATION_MISMATCH_GRACE_SECONDS', 5),
        'duration_mismatch_grace_percent' => (float) env('GAME_DURATION_MISMATCH_GRACE_PERCENT', 0.10),
        'duration_mismatch_points' => (int) env('GAME_DURATION_MISMATCH_POINTS', 0),
        'min_reliable_duration_seconds' => (int) env('GAME_MIN_RELIABLE_DURATION_SECONDS', 5),
        'min_client_duration_for_validation' => (int) env('GAME_MIN_CLIENT_DURATION_FOR_VALIDATION', 30),
        'min_score_for_duration_validation' => (int) env('GAME_MIN_SCORE_FOR_DURATION_VALIDATION', 50),
        'adaptive_score_limits' => [
            ['min_seconds' => 0, 'max_seconds' => 10, 'max_score' => 40],
            ['min_seconds' => 10, 'max_seconds' => 20, 'max_score' => 90],
            ['min_seconds' => 20, 'max_seconds' => 30, 'max_score' => 130],
            ['min_seconds' => 30, 'max_seconds' => 45, 'max_score' => 180],
            ['min_seconds' => 45, 'max_seconds' => 60, 'max_score' => 210],
            ['min_seconds' => 60, 'max_seconds' => 90, 'max_score' => 320],
            ['min_seconds' => 90, 'max_seconds' => 120, 'max_score' => 350],
            ['min_seconds' => 120, 'max_seconds' => 180, 'max_score' => 470],
            ['min_seconds' => 180, 'max_seconds' => 240, 'max_score' => 570],
            ['min_seconds' => 240, 'max_seconds' => 300, 'max_score' => 600],
            ['min_seconds' => 300, 'max_seconds' => 420, 'max_score' => 680],
            ['min_seconds' => 420, 'max_seconds' => 600, 'max_score' => 780],
            ['min_seconds' => 600, 'max_seconds' => 999999, 'max_score' => 870],
        ],
    ],

    'rate_limits' => [
        'login_per_minute' => (int) env('GAME_RATE_LIMIT_LOGIN_PER_MINUTE', 5),
        'register_per_minute' => (int) env('GAME_RATE_LIMIT_REGISTER_PER_MINUTE', 3),
        'country_check_per_minute' => (int) env('GAME_RATE_LIMIT_COUNTRY_CHECK_PER_MINUTE', 60),
        'mvp_settings_per_minute' => (int) env('GAME_RATE_LIMIT_MVP_SETTINGS_PER_MINUTE', 60),
        'leaderboard_per_minute' => (int) env('GAME_RATE_LIMIT_LEADERBOARD_PER_MINUTE', 60),
        'profile_per_minute' => (int) env('GAME_RATE_LIMIT_PROFILE_PER_MINUTE', 60),
        'authenticated_read_per_minute' => (int) env('GAME_RATE_LIMIT_AUTHENTICATED_READ_PER_MINUTE', 60),
        'auth_token_management_per_minute' => (int) env('GAME_RATE_LIMIT_AUTH_TOKEN_MANAGEMENT_PER_MINUTE', 20),
        'active_skin_per_minute' => (int) env('GAME_RATE_LIMIT_ACTIVE_SKIN_PER_MINUTE', 20),
        'session_start_per_minute' => (int) env('GAME_RATE_LIMIT_SESSION_START_PER_MINUTE', 30),
        'session_close_per_minute' => (int) env('GAME_RATE_LIMIT_SESSION_CLOSE_PER_MINUTE', 30),
        'submit_score_per_minute' => (int) env('GAME_RATE_LIMIT_SUBMIT_SCORE_PER_MINUTE', 20),
        'buy_skin_per_minute' => (int) env('GAME_RATE_LIMIT_BUY_SKIN_PER_MINUTE', 10),
    ],
];
