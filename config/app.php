<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Backend System',
        'version' => '1.0.0',
        'debug' => true,
        'url' => 'http://localhost',
    ],

    'auth' => [
        'registration_mode' => 'email',
        'password_min_length' => 8,
        'session_lifetime' => 3600,
        'remember_lifetime' => 604800,
        'password_reset_expiry' => 3600,
    ],

    'mail' => [
        'host' => '',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from_address' => '',
        'from_name' => 'Backend System',
    ],

    'access_levels' => [
        1 => 'user',
        2 => 'admin',
        3 => 'owner',
    ],

    'pagination' => [
        'per_page' => 20,
    ],
];
