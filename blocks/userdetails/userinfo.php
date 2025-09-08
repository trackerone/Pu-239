<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;

global $container, $user;

$db = $container->get(Database::class);

if ($user['info']) {
    $HTMLOUT .= "<tr><td colspan='2' class='text'>" . format_comment($user['info']) . "</td></tr>\n";
} else {
    $HTMLOUT .= "<tr><td>Info</td><td>User Info is empty</td></tr>\n";
}
if ($user['signature']) {
    $HTMLOUT .= '<tr><td>Signature</td><td>' . format_comment($user['signature']) . "</td></tr>\n";
} else {
    $HTMLOUT .= "<tr><td>Signature</td><td>Signature is empty</td></tr>\n";
}
