<?php
declare(strict_types=1);
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';
use Pu239\Database;
global $container;
$db = $container->get(Database::class);
throw new \RuntimeException('Quarantined: see public/_quarantine/comment.php');
