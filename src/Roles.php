<?php
declare(strict_types=1);

namespace Pu239;

use Delight\Auth\Role;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

$db = $container->get(Database::class);







/**
 * Class Roles.
 */
final class Roles
{
    const CODER = Role::DEVELOPER;
    const FORUM_MOD = Role::MODERATOR;
    const TORRENT_MOD = Role::MANAGER;
    const INTERNAL = Role::CREATOR;
    const UPLOADER = Role::CONTRIBUTOR;

    private function __construct()
    {
    }
}
