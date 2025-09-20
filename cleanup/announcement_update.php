<?php
declare(strict_types=1);

use DI\DependencyException;
use DI\NotFoundException;
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
function announcement_update($data): void
{
    global $db;

    $time_start = microtime(true);
    $db->run('DELETE FROM announcement_main WHERE expires < :expires', [':expires' => TIME_NOW]);

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Announcement Cleanup: Completed' . $text);
    }
}
