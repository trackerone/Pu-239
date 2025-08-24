<?php
declare(strict_types=1);

namespace Pu239\Support;

/**
 * Minimal config loader.
 * Loads array from config/database.php if present, else from env.
 */
final class Config
{
    /** @return array{dsn:string,user?:string,pass?:string,options?:array} */
    public static function database(): array
    {
        $file = __DIR__ . '/../../config/database.php';
        if (is_file($file)) {
            /** @var array $cfg */
            $cfg = require $file;
            return $cfg;
        }
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'pu239';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        return [
            'dsn' => $dsn,
            'user' => getenv('DB_USER') ?: 'root',
            'pass' => getenv('DB_PASS') ?: '',
            'options' => [],
        ];
    }
}
