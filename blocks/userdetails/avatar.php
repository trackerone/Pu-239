<?php
require_once __DIR__ . '/../../include/runtime_safe.php';

require_once __DIR__ . '/../../include/bootstrap_pdo.php';


declare(strict_types = 1);

if ($user['avatar']) {
    $HTMLOUT .= "
    <tr>
        <td class='rowhead'>" . _('Avatar') . "</td>
        <td><img src='" . url_proxy($user['avatar'], true, 250) . "' alt='" . _('Avatar') . "'></td>
    </tr>";
}
