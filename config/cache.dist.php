<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

return [
    'cache' => [
        'default' => [
            'driver' => 'memory',
            'prefix' => 'pu239_',
        ],
        'peer' => [
            'driver' => 'memcached',
            'prefix' => 'Peers_',
        ],
        'redis' => [
            'host' => '127.0.0.1',
            'password' => null,
            'port' => 6379,
            'database' => 1,
            'socket' => '/dev/shm/redis.sock',
            'use_socket' => false,
        ],
        'filesystem' => [
            'path' => '/dev/shm/pu239',
        ],
        'memcached' => [
            'host' => '127.0.0.1',
            'port' => 11211,
            'socket' => '/dev/shm/memcached.sock',
            'use_socket' => false,
        ],
    ],
];
