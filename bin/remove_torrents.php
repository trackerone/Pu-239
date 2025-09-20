#!/usr/bin/env php
<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_cli.php';

use Pu239\Database;
use Pu239\Torrent;

global $container;

$db = $container->get(Database::class);
$time_start = microtime(true);
$sql = 'SELECT id, info_hash, owner FROM torrents ORDER BY id';
$torrents = $db->run($sql)->fetchAll();

$i = 0;
$torrents_class = $container->get(Torrent::class);
foreach ($torrents as $torrent) {
    $torrents_class->delete_by_id((int) $torrent['id']);
    $torrents_class->remove_torrent($torrent['info_hash'], (int) $torrent['id'], (int) $torrent['owner']);
    ++$i;
}
$db->run('SET FOREIGN_KEY_CHECKS = 0');
$db->run('TRUNCATE torrents');
$db->run('TRUNCATE snatched');
$db->run('TRUNCATE peers');
$db->run('TRUNCATE files');
$db->run('SET FOREIGN_KEY_CHECKS = 1');

$time_end = microtime(true);
$run_time = $time_end - $time_start;
echo "$i torrents deleted. Run time: $run_time seconds\n";
