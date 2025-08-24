<?php
declare(strict_types=1);

use Pu239\Database;

require_once __DIR__ . '/../include/bittorrent.php';

global $container;
$db = $container->get(Database::class);

// Example migration: list banned emails
$sql = 'SELECT id, email FROM bannedemails ORDER BY email ASC';
$stmt = $db->prepare($sql);
$stmt->execute();
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Banned Emails</h1>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Email</th></tr>";
foreach ($emails as $row) {
    echo '<tr><td>' . (int)$row['id'] . '</td><td>' . htmlspecialchars($row['email']) . '</td></tr>';
}
echo "</table>";
