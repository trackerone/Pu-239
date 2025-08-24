<?php
declare(strict_types=1);

use Pu239\Database;

require_once __DIR__ . '/../include/bittorrent.php';

global $container;
$db = $container->get(Database::class);

// Example migration: fetch bonus logs
$sql = 'SELECT id, userid, points, date FROM bonuslog ORDER BY date DESC LIMIT 50';
$stmt = $db->prepare($sql);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Bonus Management</h1>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>User</th><th>Points</th><th>Date</th></tr>";
foreach ($logs as $row) {
    echo '<tr><td>' . (int)$row['id'] . '</td><td>' . (int)$row['userid'] . '</td><td>' . (int)$row['points'] . '</td><td>' . htmlspecialchars($row['date']) . '</td></tr>';
}
echo "</table>";
