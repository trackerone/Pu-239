<?php
declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

use Pu239\Database;

global $container, $site_config;
/** @var Pu239\Database $db */
$db = $container->get(Database::class);

// TEMPORARY STUB: Forum module under maintenance.
// The original file has been quarantined to forums/_quarantine/undelete_post.php.orig
http_response_code(503);
header('Content-Type: text/plain; charset=utf-8');
echo "Forum module is temporarily unavailable while we rebuild this section.\n";
echo "Reference: forums/_quarantine/undelete_post.php.orig\n";
return;
