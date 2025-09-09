<?php

declare(strict_types=1);

require_once __DIR__ . '/include/runtime_safe.php';
require_once __DIR__ . '/include/bootstrap_pdo.php';

use Pu239\Database;

global $container;
$db = $container->get(Database::class);

// Example migration: fetch bonus logs
$logs = $db->fetchAll('SELECT id, userid, points, date FROM bonuslog ORDER BY date DESC LIMIT 50');

echo "<h1>Bonus Management</h1>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>User</th><th>Points</th><th>Date</th></tr>";
foreach ($logs as $row) {
    echo '<tr><td>' . (int)$row['id'] . '</td><td>' . (int)$row['userid'] . '</td><td>' . (int)$row['points'] . '</td><td>' . htmlspecialchars($row['date']) . '</td></tr>';
}
echo "</table>";
