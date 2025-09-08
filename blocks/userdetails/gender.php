<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;

global $container, $site_config, $user;

$db = $container->get(Database::class);

$HTMLOUT .= "
    <tr>
        <td class='rowhead'>" . _('Gender') . "</td>
        <td>
            <img src='{$site_config['paths']['images_baseurl']}" . htmlsafechars($user['gender']) . ".gif' alt='" . htmlsafechars($user['gender']) . "' title='" . htmlsafechars($user['gender']) . "'>
        </td>
    </tr>";
