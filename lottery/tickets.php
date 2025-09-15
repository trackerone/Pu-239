<?php
declare(strict_types=1);
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';
use Pu239\Database;
global $container;
$db = $container->get(Database::class);

// TEMPORARY STUB: Lottery module under maintenance.
// Original file quarantined: lottery/_quarantine/tickets.php.orig
http_response_code(503);
header('Content-Type: text/plain; charset=utf-8');
echo "Lottery module is temporarily unavailable while we rebuild this section.\n";
echo "Reference: lottery/_quarantine/tickets.php.orig\n";
return;
