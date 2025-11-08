<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register', 'forgot-password', 'reset-password', 'email/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://ezdokani.com'],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
