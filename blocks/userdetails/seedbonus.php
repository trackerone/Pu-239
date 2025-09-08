<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;

global $container, $user, $site_config;

$db = $container->get(Database::class);

$HTMLOUT .= "
    <tr>
        <td class='rowhead'>" . _('Karma Points') . "</td>
        <td>
            <a class='is-link' href='{$site_config['paths']['baseurl']}/mybonus.php'>" . number_format((float) $user['seedbonus']) . '</a>
        </td>
    </tr>';
