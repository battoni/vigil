<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(separator: ',', string: env(key: 'CORS_ALLOWED_ORIGINS', default: 'https://app.vigil.vigil.test,https://app.vigil.vigil.test:5173'))),

    'allowed_origins_patterns' => array_filter(explode(separator: ',', string: env(key: 'CORS_ALLOWED_ORIGINS_PATTERNS', default: ''))),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
