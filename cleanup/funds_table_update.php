<?php
declare(strict_types=1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Database;

global $container;
/** @var Database $db */
$db = $container->get(Database::class);

/**
 * @param mixed $data
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function funds_table_update($data): void
{
    global $container, $db;

    $time_start = microtime(true);
    $db->run('TRUNCATE TABLE funds');
    $cache = $container->get(Cache::class);
    $cache->delete('totalfunds_');
    if ($data['clean_log']) {
        write_log('Cleanup: Funds Table truncated');
    }
    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Delete Old Funds Cleanup: Completed' . $text);
    }
}
