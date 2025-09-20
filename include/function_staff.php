<?php
declare(strict_types=1);
require_once __DIR__ . '/../include/runtime_safe.php';

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;

global $container;
$db = $container->get(Database::class);

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
    global $db;

    $values = [
        ':added' => TIME_NOW,
        ':txt' => $text,
    ];
    $db->run('INSERT INTO infolog (added, txt) VALUES (:added, :txt)', $values);
    $id = (int) $db->lastInsertId();

    return $id;
}
