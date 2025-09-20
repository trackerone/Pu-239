<?php
declare(strict_types=1);

require_once __DIR__.'/runtime_safe.php';

use Pu239\Database;

global $container;
$db = $container->get(Database::class);
