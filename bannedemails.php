<?php

declare(strict_types=1);

require_once __DIR__ . '/include/runtime_safe.php';
require_once __DIR__ . '/include/bootstrap_pdo.php';

use Pu239\Database;

global $container;
$db = $container->get(Database::class);

// Example migration: list banned emails
$emails = $db->fetchAll('SELECT id, email FROM bannedemails ORDER BY email ASC');

echo "<h1>Banned Emails</h1>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Email</th></tr>";
foreach ($emails as $row) {
    echo '<tr><td>' . (int)$row['id'] . '</td><td>' . htmlspecialchars($row['email']) . '</td></tr>';
}
echo "</table>";
