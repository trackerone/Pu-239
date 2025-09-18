<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

return [
    'database' => [
        'dsn' => 'mysql:host=localhost;port=3306;dbname=pu239;charset=utf8mb4',
        'user' => 'pu239_user',
        'pass' => 'secret',
        'options' => [],
        'use_socket' => false,
        'socket' => '/var/run/mysqld/mysqld.sock',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'pu239',
        'charset' => 'utf8mb4',
        'query_limit' => 65536,
        'debug' => false,
    ],
];
