<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The guest PWA is served from a different origin than this API in
    | production (a static host), while locally the Vite dev proxy makes
    | calls same-origin. Only the deployed guest origin(s) may read API
    | responses from a browser: set CORS_ALLOWED_ORIGINS to the exact
    | guest origin(s), comma-separated, e.g.
    | CORS_ALLOWED_ORIGINS=https://sowarder-guest.onrender.com
    |
    | The guest API is anonymous and sends no cookies, so credentials
    | support stays off.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
