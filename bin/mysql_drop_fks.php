#!/usr/bin/env php
<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_cli.php';

use Pu239\Database;

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
