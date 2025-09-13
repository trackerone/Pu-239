<?php

declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

use Pu239\Database;

global $container;
$db = $container->get(Database::class);
$now = defined('TIME_NOW') ? (int) TIME_NOW : time();

/**
 * @param array $data
 *
 * @throws \Throwable
 */
function processkill_update(array $data): void
{
    global $db, $site_config;

    $time_start = microtime(true);
    $rows = $db->fetchAll('SHOW PROCESSLIST');
    $cnt = 0;
    foreach ($rows as $arr) {
        if ($arr['db'] == $site_config['db']['database'] && $arr['Command'] === 'Sleep' && $arr['Time'] > 120) {
            $db->run('KILL :id', ['id' => (int) $arr['Id']]);
            ++$cnt;
        }
    }
    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Process Kill Cleanup: Completed' . $text);
    }
}

