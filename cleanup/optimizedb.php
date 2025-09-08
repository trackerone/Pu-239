<?php

declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;

/**
 * @param $data
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function optimizedb($data)
{
    global $container, $site_config;

    $db = $container->get(Database::class);
    $time_start = microtime(true);
    $minwaste = 1024 * 1024 * 10; // 10 MB
    $rows = $db->fetchAll(
        'SHOW TABLE STATUS FROM ' . $site_config['db']['database'] . ' WHERE Data_free > :minwaste',
        ['minwaste' => $minwaste]
    );
    $oht = '';
    $tables = [];

    foreach ($rows as $row) {
        $oht .= $row['Name'] . ',';
        $tables[] = $row['Name'];
    }
    $oht = rtrim($oht, ',');
    foreach ($tables as $table) {
        $db->run('OPTIMIZE TABLE ' . $table);
    }
    if ($data['clean_log']) {
        write_log('Auto Optimize DB Cleanup: Completed');
    }
    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log'] && $oht !== '') {
        write_log('MySQL Optimized ' . count($tables) . ' table' . plural(count($tables)) . ': ' . $oht . $text);
    }
}
