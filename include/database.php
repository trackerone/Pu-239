<?php
declare(strict_types=1);

require_once __DIR__.'/runtime_safe.php';
require_once __DIR__.'/bootstrap_pdo.php';

use Pu239\Database;

global $container;
$db = $container->get(Database::class);
