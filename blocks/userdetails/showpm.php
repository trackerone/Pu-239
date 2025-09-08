<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;

global $container, $CURUSER, $user;

$db = $container->get(Database::class);

if ($CURUSER['id'] != $user['id']) {
    if ($CURUSER['class'] >= UC_STAFF) {
        $showpmbutton = 1;
    } elseif ($user['acceptpms'] === 'yes') {
        $blocked = $db->fetch(
            'SELECT id FROM blocks WHERE userid = ? AND blockid = ?',
            [$user['id'], $CURUSER['id']]
        );
        $showpmbutton = empty($blocked);
    } elseif ($user['acceptpms'] === 'friends') {
        $friend = $db->fetch(
            'SELECT id FROM friends WHERE userid = ? AND friendid = ?',
            [$user['id'], $CURUSER['id']]
        );
        $showpmbutton = !empty($friend);
    }
}
if (isset($showpmbutton)) {
    $HTMLOUT .= "
    <tr>
        <td colspan='2' class='has-text-centered'>
            <form method='get' action='messages.php?' enctype='multipart/form-data' accept-charset='utf-8'>
                <input type='hidden' name='action' value='send_message'>
                <input type='hidden' name='receiver' value='" . (int) $user['id'] . "'>
                <input type='hidden' name='returnto' value='" . urlencode($_SERVER['REQUEST_URI']) . "'>
                <input type='submit' value='" . _('Send Message') . "' class='button is-small'>
          </form>
        </td>
    </tr>";
}
