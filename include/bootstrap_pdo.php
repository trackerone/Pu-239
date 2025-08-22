<?php
/**
 * Bootstrap PDO using environment variables (recommended for deployment).
 * Define ENV:
 *   DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET (optional, default utf8mb4)
 *
 * Example DSN: mysql:host=localhost;dbname=pu239;charset=utf8mb4
 */
require_once __DIR__ . '/DB.php';

$host = getenv('DB_HOST') ?: 'localhost';
$name = getenv('DB_NAME') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host={$host}" . ($name ? ";dbname={$name}" : '') . ";charset={$charset}";

DB::init($dsn, $user, $pass);
