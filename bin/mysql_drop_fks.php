<?php
declare(strict_types=1);

use Pu239\Database;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

global $container, $site_config;

$db = $container->get(Database::class);

$sql = "SELECT TABLE_NAME, CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_NAME LIKE '%_ibfk_%' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND TABLE_SCHEMA = :schema";
$rows = $db->fetchAll($sql, ['schema' => $site_config['db']['database']]);
foreach ($rows as $row) {
    $tbl = $row['TABLE_NAME'];
    $fk = $row['CONSTRAINT_NAME'];
    $sql = "ALTER TABLE `{$site_config['db']['database']}`.`$tbl` DROP FOREIGN KEY `$fk`";
    echo $sql . "\n";
    $db->run($sql);
}
