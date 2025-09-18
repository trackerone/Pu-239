<?php
declare(strict_types=1);

use Pu239\Database;
use PU239\Config\ConfigRepository;

require_once __DIR__ . '/runtime_safe.php';

global $container;

$db = null;
if (isset($container)) {
    try {
        $db = $container->get(Database::class);
    } catch (\Throwable $e) {
        $db = db();
    }
}

static $__db_instance = null;

/**
 * Returns a shared Pu239\Database instance.
 */
function db(): Database
{
    global $__db_instance, $container;
    if (!($__db_instance instanceof Database)) {
        $dsn = '';
        $user = '';
        $pass = '';
        $options = [];
        if (isset($container) && $container->has(ConfigRepository::class)) {
            /** @var ConfigRepository $cfg */
            $cfg = $container->get(ConfigRepository::class);
            /** @var array<string, mixed> $database */
            $database = $cfg->get('database', []);
            $dsn = (string) ($database['dsn'] ?? '');
            $user = (string) ($database['user'] ?? '');
            $pass = (string) ($database['pass'] ?? '');
            $options = is_array($database['options'] ?? null) ? $database['options'] : [];
        } else {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = (int) (getenv('DB_PORT') ?: 3306);
            $name = getenv('DB_NAME') ?: 'pu239';
            $charset = getenv('DB_CHARSET') ?: 'utf8mb4';
            $dsn = getenv('DB_DSN');
            if ($dsn === false || $dsn === null || $dsn === '') {
                $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);
            }
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            $options = [];
        }
        $__db_instance = new Database($dsn, $user, $pass, $options);
    }

    return $__db_instance;
}

/**
 * Returns the underlying PDO.
 */
function pdo(): PDO
{
    return db()->pdo();
}
