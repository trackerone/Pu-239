<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

$defaultDriver = getenv('CACHE_DRIVER') ?: 'memory';
$peerDriver = getenv('PEER_CACHE_DRIVER') ?: 'memcached';
$redisUseSocketFlag = filter_var(getenv('REDIS_USE_SOCKET'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$redisUseSocket = $redisUseSocketFlag ?? false;
$memcachedUseSocketFlag = filter_var(getenv('MEMCACHED_USE_SOCKET'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$memcachedUseSocket = $memcachedUseSocketFlag ?? false;

return [
    'cache' => [
        'default' => [
            'driver' => $defaultDriver,
            'prefix' => getenv('CACHE_PREFIX') ?: 'pu239_',
        ],
        'peer' => [
            'driver' => $peerDriver,
            'prefix' => getenv('PEER_CACHE_PREFIX') ?: 'Peers_',
        ],
        'redis' => [
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'password' => getenv('REDIS_PASSWORD') ?: null,
            'port' => (int) (getenv('REDIS_PORT') ?: 6379),
            'database' => (int) (getenv('REDIS_DATABASE') ?: 1),
            'socket' => getenv('REDIS_SOCKET') ?: '/dev/shm/redis.sock',
            'use_socket' => $redisUseSocket,
        ],
        'filesystem' => [
            'path' => getenv('FILES_CACHE_PATH') ?: '/dev/shm/pu239',
        ],
        'memcached' => [
            'host' => getenv('MEMCACHED_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('MEMCACHED_PORT') ?: 11211),
            'socket' => getenv('MEMCACHED_SOCKET') ?: '/dev/shm/memcached.sock',
            'use_socket' => $memcachedUseSocket,
        ],
    ],
];
