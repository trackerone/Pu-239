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
 *
 * @return bool|void
 */
function tvmaze_update($data)
{
    global $container, $BLOCKS;

    $time_start = microtime(true);
    if (!$BLOCKS['tvmaze_api_on']) {
        return;
    }
    $fluent = $container->get(Database::class);
    $max = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
    }

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log("TVMaze ID's Cleanup completed" . $text);
    }
}

/**
 * @param $param
 * @param bool $int
 *
 * @return mixed|string
 */
function get_or_empty($param, bool $int)
{
    if (!empty($param)) {
        if (is_int($param)) {
            return $param;
        }

        return htmlsafechars($param);
    }

    if ($int) {
        return 0;
    }

    return '';
}
