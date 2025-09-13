<?php
declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

use Pu239\Database;
use Pu239\Userblock;
use Pu239\Usersachiev;

global $container;

$db = $container->get(Database::class);

$users = $db->run('SELECT id FROM users')->fetchAll();

$achieve = $container->get(Usersachiev::class);
$userblock = $container->get(Userblock::class);
foreach ($users as $user) {
    $achieve->add(['userid' => $user['id']]);
    $userblock->add(['userid' => $user['id']]);
}
