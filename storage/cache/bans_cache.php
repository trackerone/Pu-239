<?php
declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    exit;
}

$root = dirname(__DIR__, 2);
require_once $root . '/include/runtime_safe.php';
require_once $root . '/include/bootstrap_pdo.php';

use Pu239\Database;

global $container;
$db = $container->get(Database::class);

$bans = [];
