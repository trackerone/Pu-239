<?php
require_once __DIR__ . '/runtime_safe.php';

require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;

/**
 * @param $text
 *
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws DependencyException
 *
 * @return bool|int
 */
function write_info($text)
{
    $values = [
        'added' => TIME_NOW,
        'txt' => $text,
    ];
    global $container;
    $fluent = $container->get(Database::class);
    $id = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    return $id;
}
