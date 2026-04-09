<?php

use App\Support\FrontendOrigins;

$appEnvironment = (string) env('APP_ENV', 'production');
$defaultOrigins = in_array($appEnvironment, ['local', 'testing'], true)
    ? FrontendOrigins::defaultLocal()
    : [];
$configuredOrigins = FrontendOrigins::parse((string) env('FRONTEND_URLS', implode(',', $defaultOrigins)));
$originPatterns = FrontendOrigins::patterns($configuredOrigins);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | This API is bearer-token based. CORS controls which browser origins may
    | call API routes; it is separate from backend trusted-host validation.
    | Origins are exact-match values normalized from FRONTEND_URLS.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $configuredOrigins,

    'allowed_origins_patterns' => $originPatterns,

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-CSRF-TOKEN',
        'X-Requested-With',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
