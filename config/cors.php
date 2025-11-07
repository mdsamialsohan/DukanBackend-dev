<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', '/register', '/login'],
    'allowed_methods' => ['*'],
    'allowed_origins' => explode(',', env('FRONTEND_URL', 'https://ezdokani.com')),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
