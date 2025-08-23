<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;

/**
 * @param $data
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 */
function visible_update($data)
{
    global $container, $site_config;

    $fluent = $container->get(Database::class);

    $time_start = microtime(true);
    $deadtime_tor = get_date(TIME_NOW - $site_config['site']['max_dead_torrent_time'], 'MYSQL', 1, 0);
    $set = [
        'visible' => 'no',
    ];
    $fluent->update('torrents')
           ->set($set)
           ->where('visible = "yes"')
           ->where('last_action < ?', $deadtime_tor)
           ->execute();

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Torrent Visible Cleanup completed' . $text);
    }
}
