#!/usr/bin/env php
<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_cli.php';

use Pu239\Database;

global $container;
$db = $container->get(Database::class);

echo time() . "\n";
