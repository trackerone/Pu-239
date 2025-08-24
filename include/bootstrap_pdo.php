<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime_safe.php';

use Pu239\Database;
use Pu239\Support\Config;

static $__db_instance = null;

/**
 * Returns a shared Pu239\Database instance.
 */
function db(): Database
{
    global $__db_instance;
    if (!$__db_instance instanceof Database) {
        $cfg = Config::database();
        $dsn = $cfg['dsn'] ?? '';
        $user = $cfg['user'] ?? '';
        $pass = $cfg['pass'] ?? '';
        $options = $cfg['options'] ?? [];
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
