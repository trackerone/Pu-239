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
function user_update($data)
{
    global $container;

    $time_start = microtime(true);
    $dt = TIME_NOW;
    $fluent = $container->get(Database::class);
    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('User Cleanup completed' . $text);
    }
}
