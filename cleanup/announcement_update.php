<?php
declare(strict_types=1);

use Pu239\Database;
use DI\DependencyException;
use DI\NotFoundException;

global $container;
$db = $container->get(Database::class);

/**
 * @param mixed $data
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function announcement_update($data)
{
    global $container, $db;

    $time_start = microtime(true);
    $db->run('DELETE FROM announcement_main WHERE expires < ?', [TIME_NOW]);

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Announcement Cleanup: Completed' . $text);
    }
}
