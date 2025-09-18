<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

return [
    'mail' => [
        'transport' => 'smtp',
        'smtp' => [
            'enabled' => false,
            'host' => 'smtp.example.com',
            'auth' => true,
            'username' => 'user@example.com',
            'password' => 'secret',
            'secure' => 'tls',
            'port' => 587,
        ],
        'from' => [
            'address' => 'noreply@example.com',
            'name' => 'Pu-239',
        ],
    ],
];
