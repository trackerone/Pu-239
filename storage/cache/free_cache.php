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
?>
[{"modifier":1,"begin":1553184633,"expires":1553271033,"setby":"darkalchemy","title":"Dummy","message":"Dummy"}]
