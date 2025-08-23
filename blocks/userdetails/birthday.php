<?php
require_once __DIR__ . '/../../include/runtime_safe.php';

require_once __DIR__ . '/../../include/bootstrap_pdo.php';


declare(strict_types = 1);

$age = $birthday = '';
if ($user['birthday'] != '1970-01-01') {
    $d1 = new DateTime(date('Y-m-d', TIME_NOW));
    $d2 = new DateTime($user['birthday']);
    $diff = $d2->diff($d1);

    $HTMLOUT .= "
        <tr>
            <td class='rowhead'>" . _('Age') . '</td>
            <td>' . htmlsafechars((string) $diff->y) . "</td>
        </tr>
        <tr>
            <td class='rowhead'>" . _('Birthday') . '</td>
            <td>' . htmlsafechars($user['birthday']) . '</td>
        </tr>';
}
