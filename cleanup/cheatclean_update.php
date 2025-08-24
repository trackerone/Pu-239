<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

use DI\DependencyException;
use DI\NotFoundException;

/**
 * @param $data
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 */
function cheatclean_update($data)
{
    $time_start = microtime(true);
    $dt = (TIME_NOW - (30 * 86400));
    sql_query('DELETE FROM cheaters WHERE added < ' . sqlesc($dt)) or sqlerr(__FILE__, __LINE__);
    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Cheaters List Cleanup: Removed old cheater entrys. Completed' . $text);
    }
}
