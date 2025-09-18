<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
$name = getenv('DB_NAME') ?: 'pu239';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$socket = getenv('DB_SOCKET') ?: '/var/run/mysqld/mysqld.sock';
$useSocketFlag = filter_var(getenv('DB_USE_SOCKET'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$useSocket = $useSocketFlag ?? false;
$dsn = getenv('DB_DSN');
if ($dsn === false || $dsn === null || $dsn === '') {
    if ($useSocket) {
        $dsn = sprintf('mysql:unix_socket=%s;dbname=%s;charset=%s', $socket, $name, $charset);
    } else {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);
    }
}

$debugFlag = filter_var(getenv('DB_DEBUG'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$persistentFlag = filter_var(getenv('DB_PERSISTENT'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

return [
    'database' => [
        'dsn' => $dsn,
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => $persistentFlag ?? false,
        ],
        'use_socket' => $useSocket,
        'socket' => $socket,
        'host' => $host,
        'port' => $port,
        'database' => $name,
        'charset' => $charset,
        'query_limit' => (int) (getenv('DB_QUERY_LIMIT') ?: 65536),
        'debug' => $debugFlag ?? true,
    ],
];
