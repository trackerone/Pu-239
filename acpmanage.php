<?php
declare(strict_types=1);

use Pu239\Database;

require_once __DIR__ . '/../include/bittorrent.php';

global $container;
$db = $container->get(Database::class);

// Example migration for ACP manage - load settings from DB
$sql = 'SELECT name, value FROM settings ORDER BY name ASC';
$stmt = $db->prepare($sql);
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>ACP Manage</h1>";
echo "<ul>";
foreach ($settings as $row) {
    echo '<li>' . htmlspecialchars($row['name']) . ' = ' . htmlspecialchars($row['value']) . '</li>';
}
echo "</ul>";
