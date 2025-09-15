<?php
declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

use Pu239\Database;
global $container;
$db = $container->get(Database::class);

require_once __DIR__ . '/../include/app.php';
require_once CONFIG_DIR . 'classes.php';

$agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Not Provided by Client';
