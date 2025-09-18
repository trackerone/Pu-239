<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

return [
    'storage' => [
        'bucket' => [
            'allowed' => true,
            'max_size' => 5242880,
        ],
        'filesystem' => [
            'path' => '/var/www/pu239/storage',
        ],
    ],
    'webserver' => [
        'user' => 'www-data',
    ],
];
