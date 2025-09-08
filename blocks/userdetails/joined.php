<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;

global $container, $lastseen, $joindate;

$db = $container->get(Database::class);

$HTMLOUT .= "
    <tr>
        <td class='rowhead'>" . _('Join Date') . "</td>
        <td>{$joindate}</td>
    </tr>
    <tr>
        <td class='rowhead'>" . _('Last Seen') . "</td>
        <td>{$lastseen}</td>
    </tr>";
