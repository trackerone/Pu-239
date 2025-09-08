<?php

declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;

/**
 * @param mixed $data
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function announcement_update($data)
{
    global $container;

    $time_start = microtime(true);
    $db = $container->get(Database::class);
    $db->run('DELETE FROM announcement_main WHERE expires < ?', [TIME_NOW]);

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Announcement Cleanup: Completed' . $text);
    }
}
