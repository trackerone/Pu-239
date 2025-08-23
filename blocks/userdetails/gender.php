<?php
require_once __DIR__ . '/../../include/runtime_safe.php';

require_once __DIR__ . '/../../include/bootstrap_pdo.php';


declare(strict_types = 1);
global $site_config, $user;

$HTMLOUT .= "
    <tr>
        <td class='rowhead'>" . _('Gender') . "</td>
        <td>
            <img src='{$site_config['paths']['images_baseurl']}" . htmlsafechars($user['gender']) . ".gif' alt='" . htmlsafechars($user['gender']) . "' title='" . htmlsafechars($user['gender']) . "'>
        </td>
    </tr>";
