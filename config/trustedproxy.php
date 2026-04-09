<?php

use Illuminate\Http\Request;

return [
    'proxies' => env('TRUSTED_PROXIES'),

    'headers' => env('TRUSTED_PROXY_HEADERS') ?: (
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_PREFIX |
        Request::HEADER_X_FORWARDED_AWS_ELB
    ),
];
