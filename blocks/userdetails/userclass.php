<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;

global $container, $user;

$db = $container->get(Database::class);

$HTMLOUT .= "
        <tr>
            <td class='rowhead'>" . _('Class') . '</td>
            <td>' . get_user_class_name((int) $user['class']) . "&#160;&#160;<img src='" . get_user_class_image((int) $user['class']) . "' alt='" . get_user_class_name((int) $user['class']) . "' title='" . get_user_class_name((int) $user['class']) . "'></td>
        </tr>";
