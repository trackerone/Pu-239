<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use PU239\Security\AuthZ;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Message;

global $container, $CURUSER;
<<<<<< codex/enforce-centralized-authorization-checks-s6jwwl
=======
<<<<<< codex/enforce-centralized-authorization-checks-vacoay
=======
// >>>>>> PU239:authz-gate-3
>>>>>> master
>>>>>> master
AuthZ::requireRole('admin');
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

/** @var Database $db */
$db = $container->get(Database::class);
/** @var Cache $cache */
$cache = $container->get(Cache::class);
/** @var Message $messages */
$messages = $container->get(Message::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$self = $s($_SERVER['PHP_SELF'] ?? '');
$baseurlRaw = (string) $config->get('paths.baseurl');
$baseurl = $s($baseurlRaw);

$HTMLOUT = '';
$this_url = $_SERVER['SCRIPT_NAME'] ?? '';
$do = (isset($_GET['do']) && $_GET['do'] === 'disabled') ? 'disabled' : 'leechwarn';
$requestUri = $s($_SERVER['REQUEST_URI'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO(2025): csrf
    $r = isset($_POST['ref']) ? (string) $_POST['ref'] : $this_url;
    $uids = isset($_POST['users']) && is_array($_POST['users']) ? array_values(array_unique(array_map('intval', $_POST['users']))) : [];
    if (empty($uids)) {
        stderr(_('Error'), _("Looks like you didn't select any user!"));
    }
    $valid = ['unwarn', 'disable', 'delete'];
    $act = isset($_POST['action']) && in_array($_POST['action'], $valid, true) ? (string) $_POST['action'] : '';
    if ($act === '') {
        stderr(_('Error'), _('Something went wrong!'));
    }

    if ($act === 'unwarn') {
        // Clear leechwarn + reason and notify users
        $placeholders = implode(',', array_fill(0, count($uids), '?'));
        $db->run("UPDATE users SET leechwarn = 0, warn_reason = NULL WHERE id IN ($placeholders)", $uids);
        foreach ($uids as $uid) {
            audit_log(
                $CURUSER['id'] ?? null,
                'user.unban',
                [
                    'target' => (int) $uid,
                    'reason' => 'leechwarn.remove',
                ]
            );
        }

        $body = _fe('Hey, your Leech warning was removed by {0}. Please keep in your best behaviour from now on.', $CURUSER['username']);
        $sub  = _('Leech Warning Removed');
        $now  = TIME_NOW;

        $buffer = [];
        foreach ($uids as $uid) {
            $cache->delete('user_' . (int) $uid);
            $buffer[] = [
                'receiver' => (int) $uid,
                'added'    => $now,
                'msg'      => $body,
                'subject'  => $sub,
            ];
        }
        if (!empty($buffer)) {
            $messages->insert($buffer);
        }

        header('Refresh: 2; url=' . $r);
        stderr(_('Success'), _pfe("{0} user's leech warning removed", "{0} users' leech warnings removed", count($uids)));
    } elseif ($act === 'disable') {
        // Disable accounts and clear leech warn
        $reason = _fe('Disabled for leech warning by {0} on {1}', $CURUSER['username'], get_date(TIME_NOW, 'DATE', 1));
        $params = [$reason];
        $placeholders = implode(',', array_fill(0, count($uids), '?'));
        $params = array_merge($params, $uids);

        $db->run("UPDATE users SET status = 2, disable_reason = ?, leechwarn = 0 WHERE id IN ($placeholders)", $params);
        foreach ($uids as $uid) {
            $cache->delete('user_' . (int) $uid);
            audit_log(
                $CURUSER['id'] ?? null,
                'user.ban',
                [
                    'target' => (int) $uid,
                    'reason' => 'leechwarn.disable',
                ]
            );
        }

        header('Refresh: 2; url=' . $r);
        stderr(_('Success'), _pfe('{0} user disabled', '{0} users disabled', count($uids)));
    } elseif ($act === 'delete') {
        if (!has_access((int) $CURUSER['class'], UC_SYSOP, 'coder')) {
            stderr(_('Error'), _('Permission denied.'));
        }
        $placeholders = implode(',', array_fill(0, count($uids), '?'));
        $db->run("DELETE FROM users WHERE id IN ($placeholders)", $uids);
        foreach ($uids as $uid) {
            $cache->delete('user_' . (int) $uid);
            audit_log(
                $CURUSER['id'] ?? null,
                'user.ban',
                [
                    'target' => (int) $uid,
                    'reason' => 'leechwarn.delete',
                ]
            );
        }

        header('Refresh: 2; url=' . $r);
        stderr(_('Success'), _pfe('{0} user deleted', '{0} users deleted', count($uids)));
    }

    app_halt('Exit called');
}

// -------------------------------------------
// Views
// -------------------------------------------
$base = $baseurl;
switch ($do) {
    case 'disabled':
        $query = "SELECT id, username, class, downloaded, uploaded,
                         IF(downloaded>0, ROUND((uploaded/downloaded),2), '---') AS ratio,
                         disable_reason, registered, last_access
                  FROM users
                  WHERE status = 2
                  ORDER BY last_access DESC";
        $title = _('Disabled users');
        $link  = '<a href="' . $base . '/staffpanel.php?tool=leechwarn&amp;action=leechwarn&amp;do=warned">'
               . _('Leech warned users') . '</a>';
        break;

    case 'leechwarn':
    default:
        $query = "SELECT id, username, class, downloaded, uploaded,
                         IF(downloaded>0, ROUND((uploaded/downloaded),2), '---') AS ratio,
                         warn_reason, leechwarn, registered, last_access
                  FROM users
                  WHERE leechwarn >= 1
                  ORDER BY last_access DESC, leechwarn DESC";
        $title = _('Leech Warned users');
        $link  = '<a href="' . $base . '/staffpanel.php?tool=leechwarn&amp;action=leechwarn&amp;do=disabled">'
               . _('Disabled users') . '</a>';
        break;
}

$rows = $db->fetchAll($query);
$count = count($rows);

$HTMLOUT .= "
    <ul class='level-center bg-06'>
        <li class='is-link margin10'>
            $link
        </li>
    </ul>";
$HTMLOUT .= "<h2 class='has-text-centered'>" . _('total - ') . " $count " . _(' user') . ' ' . ($count > 1 ? _('s') : '') . '</h2>';

if ($count === 0) {
    $HTMLOUT .= stdmsg(_('Hey'), _('There is no ') . strtolower($title));
} else {
    $HTMLOUT .= "
    <form action='{$self}?tool=leechwarn&amp;action=leechwarn' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";
    $heading = '
        <tr>
            <th>' . _('User') . '</th>
            <th>' . _('Ratio') . '</th>
            <th>' . _('Class') . '</th>
            <th>' . _('Last access') . '</th>
            <th>' . _('Joined') . "</th>
            <th><input type='checkbox' id='checkThemAll'></th>
        </tr>";
    $body = '';
    foreach ($rows as $a) {
        $ratio = is_numeric($a['ratio']) ? (float) $a['ratio'] : '---';
        $tip = ($do === 'leechwarn'
            ? _('Leech Warned for: ') . htmlsafechars((string) ($a['warn_reason'] ?? '')) . '<br>'
              . _(' Warned till ') . get_date((int) $a['leechwarn'], 'DATE', 1) . ' - ' . mkprettytime((int) $a['leechwarn'] - TIME_NOW)
            : _('Disabled for ') . htmlsafechars((string) ($a['disable_reason'] ?? ''))
        );

        $body .= "
        <tr>
            <td><a href='userdetails.php?id=" . (int) $a['id'] . "' class='tooltipper' title='$tip'>" . htmlsafechars((string) $a['username']) . '</a></td>
            <td>' . $ratio . "<br><span class='small'><b>" . _('D: ') . '</b>' . mksize((int) $a['downloaded']) . '&#160;<b>' . _('U: ') . '</b> ' . mksize((int) $a['uploaded']) . '</span></td>
            <td>' . get_user_class_name((int) $a['class']) . '</td>
            <td>' . get_date((int) $a['last_access'], 'LONG', 0, 1) . '</td>
            <td>' . get_date((int) $a['registered'], 'DATE', 1) . "</td>
            <td><input type='checkbox' name='users[]' value='" . (int) $a['id'] . "'></td>
        </tr>";
    }

    $HTMLOUT .= main_table($body, $heading, null, null, 'table-striped', 'checkbox_container');
    $HTMLOUT .= "
        <div class='has-text-centered margin20'>
            <select name='action'>
                <option value='unwarn'>" . _('Unwarn') . "</option>
                <option value='disable'>" . _('Disable') . "</option>
                <option value='delete' " . (!has_access((int) $CURUSER['class'], UC_SYSOP, 'coder') ? 'disabled' : '') . '>' . _('Delete') . "</option>
            </select>
                &raquo;
            <input type='submit' value='" . _('Apply') . "' class='button is-small'>
            <input type='hidden' value='{$requestUri}' name='ref'>
        </div>
    </form>";
}

$breadcrumbs = [
    "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$self}'>" . $s($title) . '</a>',
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
