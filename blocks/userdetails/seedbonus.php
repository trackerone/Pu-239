<?php
require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/mysql_compat.php';


declare(strict_types = 1);
global $user, $site_config;

$HTMLOUT .= "
    <tr>
        <td class='rowhead'>" . _('Karma Points') . "</td>
        <td>
            <a class='is-link' href='{$site_config['paths']['baseurl']}/mybonus.php'>" . number_format((float) $user['seedbonus']) . '</a>
        </td>
    </tr>';
