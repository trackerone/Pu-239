<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;

global $container, $CURUSER, $user;

$db = $container->get(Database::class);

if ($CURUSER['id'] == $user['id'] || $user['paranoia'] < 2) {
    $HTMLOUT .= "
        <tr>
            <td class='rowhead'>" . _('Profile Views') . "</td>
            <td><a href='staffpanel.php?tool=user_hits&amp;id=$id'>" . number_format((int) $user['hits']) . '</a></td>
        </tr>';
}
