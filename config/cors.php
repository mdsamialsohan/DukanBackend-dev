<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', '/register', '/login'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://ezdokani.com', 'https://www.ezdokani.com'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
