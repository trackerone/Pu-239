<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;

global $container;

$db = $container->get(Database::class);

if ($user['browser'] != '') {
    $browser = htmlsafechars($user['browser']);
} else {
    $browser = _('No browser recorded yet');
}
$HTMLOUT .= "<tr><td class='rowhead'>" . _('User Browser') . "</td><td>{$browser}</td></tr>";
