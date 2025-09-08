<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;

global $container, $user;

$db = $container->get(Database::class);

$member_reputation = get_reputation($user, 'users');
$HTMLOUT .= "
    <tr>
        <td class='rowhead'>" . _('Reputation') . "</td>
        <td>{$member_reputation}</td>
    </tr>";
