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

    'signature' => [
        'enabled' => (bool) env('GAME_SIGNATURE_ENABLED', true),
        'secret' => env('GAME_SIGNATURE_SECRET'),
        'max_skew_seconds' => (int) env('GAME_SIGNATURE_MAX_SKEW_SECONDS', 60),
        'nonce_ttl_seconds' => (int) env('GAME_NONCE_TTL_SECONDS', 120),
        'nonce_store' => env('GAME_NONCE_CACHE_STORE', 'redis'),
        'headers' => [
            'timestamp' => 'X-Timestamp',
            'nonce' => 'X-Nonce',
            'signature' => 'X-Signature',
        ],
    ],

    'score_validation' => [
        'min_score' => (int) env('GAME_MIN_SCORE', 0),
        'max_score' => (int) env('GAME_MAX_SCORE', 1000000),
        'min_duration_seconds' => (int) env('GAME_MIN_DURATION_SECONDS', 5),
        'max_duration_seconds' => (int) env('GAME_MAX_DURATION_SECONDS', 7200),
    ],

    'rate_limits' => [
        'login_per_minute' => (int) env('GAME_RATE_LIMIT_LOGIN_PER_MINUTE', 5),
        'register_per_minute' => (int) env('GAME_RATE_LIMIT_REGISTER_PER_MINUTE', 3),
        'profile_per_minute' => (int) env('GAME_RATE_LIMIT_PROFILE_PER_MINUTE', 60),
        'session_start_per_minute' => (int) env('GAME_RATE_LIMIT_SESSION_START_PER_MINUTE', 30),
        'submit_score_per_minute' => (int) env('GAME_RATE_LIMIT_SUBMIT_SCORE_PER_MINUTE', 20),
        'buy_skin_per_minute' => (int) env('GAME_RATE_LIMIT_BUY_SKIN_PER_MINUTE', 10),
    ],

    'score_rewards' => [
        'enabled' => (bool) env('GAME_SCORE_REWARDS_ENABLED', false),
        'coins_per_score_point' => (int) env('GAME_SCORE_REWARD_COINS_PER_SCORE_POINT', 0),
        'max_coins_per_submission' => (int) env('GAME_SCORE_REWARD_MAX_COINS_PER_SUBMISSION', 0),
    ],

    'anti_fraud' => [
        'max_score' => (int) env('GAME_MAX_SCORE', 1000000),
        'high_score_threshold' => (int) env('GAME_HIGH_SCORE_THRESHOLD', 10000),
        'min_duration_seconds' => (int) env('GAME_MIN_DURATION_SECONDS', 5),
        'max_duration_seconds' => (int) env('GAME_MAX_DURATION_SECONDS', 7200),
        'max_high_score_submissions_per_hour' => (int) env('GAME_MAX_HIGH_SCORE_SUBMISSIONS_PER_HOUR', 10),
        'suspicious_repeat_metadata_window_minutes' => (int) env('GAME_SUSPICIOUS_REPEAT_METADATA_WINDOW_MINUTES', 10),
    ],
];
