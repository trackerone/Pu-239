<?php

declare(strict_types=1);

require_once __DIR__ . '/include/runtime_safe.php';
require_once __DIR__ . '/include/bootstrap_pdo.php';

use Pu239\Database;

global $container;
$db = $container->get(Database::class);

// Example migration for ACP manage - load settings from DB
$settings = $db->fetchAll('SELECT name, value FROM settings ORDER BY name ASC');

echo "<h1>ACP Manage</h1>";
echo "<ul>";
foreach ($settings as $row) {
    echo '<li>' . htmlspecialchars($row['name']) . ' = ' . htmlspecialchars($row['value']) . '</li>';
}
echo "</ul>";
