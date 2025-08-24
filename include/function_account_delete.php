<?php
require_once __DIR__ . '/runtime_safe.php';

require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\User;

/**
 *
 * @param int $userid
 *
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws DependencyException
 *
 * @return string:bool
 */
function account_delete(int $userid)
{
    global $container;
$db = $container->get(Database::class);;

    if (empty($userid)) {
        return false;
    }
    $users_class = $container->get(User::class);
    $user = $users_class->getUserFromId($userid);
    $username = $user['username'];
    $cache = $container->get(Cache::class);
    $cache->delete('all_users_');
    $cache->delete('user_' . $userid);

    $db->run(");
    $db->run(");
    $db->run(");
    $db->run(");
    $db->run(");

    return $username;
}
