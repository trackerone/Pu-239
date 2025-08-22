<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);
global $user;

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
