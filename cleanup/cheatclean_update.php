<?php
declare(strict_types=1);



use Pu239\Database;

global $container;
$db = $container->get(Database::class);
$now = defined('TIME_NOW') ? (int) TIME_NOW : time();

/**
 * @param array $data
 *
 * @throws \Throwable
 */
function cheatclean_update(array $data): void
{
    global $db, $now;

    $time_start = microtime(true);
    $dt = $now - (30 * 86400);
    $db->run('DELETE FROM cheaters WHERE added < ?', [$dt]);
    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Cheaters List Cleanup: Removed old cheater entrys. Completed' . $text);
    }
}
