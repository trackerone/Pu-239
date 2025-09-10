<?php
declare(strict_types=1);

$db = $container->get(Database::class);

require_once __DIR__ . '/runtime_safe.php';

require_once __DIR__ . '/bootstrap_pdo.php';


use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;

/**
 * @param $text
 *
 * @throws NotFoundException
 * @throws \PDOException
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
    // $fluent removed — use $this->db (ExtendedPdo)
    $sql = "INSERT INTO infolog (/* columns */) VALUES (/* values */)";
$id = $db->perform($sql, $values);

    return $id;
}
