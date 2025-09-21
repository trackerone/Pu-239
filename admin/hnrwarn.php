<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Message;


global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

/** @var Database $db */
$db = $container->get(Database::class);
/** @var Cache $cache */
$cache = $container->get(Cache::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$HTMLOUT = '';
$this_url = $_SERVER['SCRIPT_NAME'] ?? '';
$do = (isset($_GET['do']) && $_GET['do'] === 'disabled') ? 'disabled' : 'hnrwarn';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = isset($_POST['ref']) ? (string) $_POST['ref'] : $this_url;
    $uids = isset($_POST['users']) && is_array($_POST['users']) ? array_map('intval', $_POST['users']) : [];

    if (empty($uids)) {
        stderr(_('Error'), _("Looks like you didn't select any user!"));
    }

    $valid = ['unwarn', 'disable', 'delete'];
    $act = isset($_POST['action']) && in_array($_POST['action'], $valid, true) ? (string) $_POST['action'] : '';

    if ($act === '') {
        stderr(_('Error'), _('Something went wrong!'));
    }

    // Run action
    if ($act === 'unwarn') {
        // Clear HnR warn and reason, notify users
        /** @var Message $messages */
        $messages = $container->get(Message::class);
        $buffer = [];
        $sub = _('HnR Warning Removed');
        $bodyTpl = _fe('Hey, your Hit and Run warning was removed by {0}. Please keep your best behaviour from now on.', $CURUSER['username']);

        $db->run(
            'UPDATE users SET hnrwarn = :no, warn_reason = NULL WHERE id IN (' . implode(',', array_fill(0, count($uids), '?')) . ')',
            array_merge([':no' => 'no'], $uids)
        );

        foreach ($uids as $id) {
            $cache->delete('user_' . (int) $id);
            $buffer[] = [
                'receiver' => (int) $id,
                'added'    => TIME_NOW,
                'msg'      => $bodyTpl,
                'subject'  => $sub,
            ];
        }
        if (!empty($buffer)) {
            $messages->insert($buffer);
        }

        header('Refresh: 2; url=' . $r);
        stderr(_('Success'), _pfe("{0} user's HnR warning removed", "{0} users' HnR warnings removed", count($uids)));
    } elseif ($act === 'disable') {
        // Disable accounts and clear warning
        $reason = _fe('Disabled for HnR by {0} on {1}', $CURUSER['username'], get_date(TIME_NOW, 'DATE', 1));
        $params = [$reason, 'no'];
        $placeholders = implode(',', array_fill(0, count($uids), '?'));
        $db->run(
            "UPDATE users
             SET status = 2, disable_reason = ?, hnrwarn = ?
             WHERE id IN ($placeholders)",
            array_merge($params, $uids)
        );
        foreach ($uids as $id) {
            $cache->delete('user_' . (int) $id);
        }

        header('Refresh: 2; url=' . $r);
        stderr(_('Success'), _pfe('{0} user disabled', '{0} users disabled', count($uids)));
    } elseif ($act === 'delete') {
        if (!has_access((int) $CURUSER['class'], UC_SYSOP, 'coder')) {
            stderr(_('Error'), _('Permission denied.'));
        }
        $placeholders = implode(',', array_fill(0, count($uids), '?'));
        $db->run("DELETE FROM users WHERE id IN ($placeholders)", $uids);
        foreach ($uids as $id) {
            $cache->delete('user_' . (int) $id);
        }

        header('Refresh: 2; url=' . $r);
        stderr(_('Success'), _pfe('{0} user deleted', '{0} users deleted', count($uids)));
    }

    app_halt('Exit called');
}

// -------------------------------------------
// Views
// -------------------------------------------
switch ($do) {
    case 'disabled':
        $query = "SELECT id, username, class, downloaded, uploaded, IF(downloaded>0, round((uploaded/downloaded),2), '---') AS ratio, disable_reason, registered, last_access
                  FROM users WHERE status = 2 ORDER BY last_access DESC";
        $title = _('Disabled users');
        $link = '<a href="staffpanel.php?tool=hnrwarn&amp;action=hnrwarn&amp;do=warned">' . _('Hit and Run warned users') . '</a>';
        break;

    case 'hnrwarn':
    default:
        $query = "SELECT id, username, class, downloaded, uploaded, IF(downloaded>0, round((uploaded/downloaded),2), '---') AS ratio, warn_reason, hnrwarn, registered, last_access
                  FROM users WHERE hnrwarn='yes' ORDER BY last_access DESC, hnrwarn DESC";
        $title = _('Hit and Run Warned users');
        $link = '<a href="staffpanel.php?tool=hnrwarn&amp;action=hnrwarn&amp;do=disabled">' . _('disabled users') . '</a>';
        break;
}

$rows = $db->fetchAll($query);
$count = count($rows);

if ($count === 0) {
    $HTMLOUT .= stdmsg(_('Hey'), _('There are no ') . strtolower($title));
} else {
    $HTMLOUT .= "<form action='staffpanel.php?tool=hnrwarn&amp;action=hnrwarn' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
        <table id='checkbox_container' style='border-collapse:separate;'>
        <tr>
            <td class='colhead'>" . _('User') . "</td>
            <td class='colhead' nowrap='nowrap'>" . _('Ratio') . "</td>
            <td class='colhead' nowrap='nowrap'>" . _('Class') . "</td>
            <td class='colhead' nowrap='nowrap'>" . _('Last access') . "</td>
            <td class='colhead' nowrap='nowrap'>" . _('Joined') . "</td>
            <td class='colhead' nowrap='nowrap'><input type='checkbox' id='checkThemAll'></td>
        </tr>";

    foreach ($rows as $a) {
        $tip = ($do === 'hnrwarn'
            ? _('Hit and run Warned for: ') . htmlsafechars((string) ($a['warn_reason'] ?? ''))
            : _('Disabled for ') . htmlsafechars((string) ($a['disable_reason'] ?? ''))
        );
        $HTMLOUT .= "<tr>
                  <td><a href='userdetails.php?id=" . (int) $a['id'] . "' class='tooltipper' title='$tip'>" . htmlsafechars((string) $a['username']) . "</a></td>
                  <td nowrap='nowrap'>" . (is_numeric($a['ratio']) ? (float) $a['ratio'] : '---') . "<br><span class='small'><b>" . _('D:') . '</b>' . mksize((int) $a['downloaded']) . '&#160;<b>' . _('U:') . '</b> ' . mksize((int) $a['uploaded']) . "</span></td>
                  <td nowrap='nowrap'>" . get_user_class_name((int) $a['class']) . "</td>
                  <td nowrap='nowrap'>" . get_date((int) $a['last_access'], 'LONG', 0, 1) . "</td>
                  <td nowrap='nowrap'>" . get_date((int) $a['registered'], 'DATE', 1) . "</td>
                  <td nowrap='nowrap'><input type='checkbox' name='users[]' value='" . (int) $a['id'] . "'></td>
                </tr>";
    }

    $HTMLOUT .= "<tr>
            <td colspan='6' class='colhead'>
                <select name='action'>
                    <option value='unwarn'>" . _('Unwarn') . "</option>
                    <option value='disable'>" . _('Disable') . "</option>
                    <option value='delete' " . (!has_access((int) $CURUSER['class'], UC_SYSOP, 'coder') ? 'disabled' : '') . '>' . _('Delete') . "</option>
                </select>
                &raquo;
                <input type='submit' value='" . _('Apply') . "'>
                <input type='hidden' value='" . htmlsafechars($_SERVER['REQUEST_URI']) . "' name='ref'>
            </td>
            </tr>
            </table>
            </form>";
}

$title = $title ?? _('HnR Warn');
$breadcrumbs = [
    "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
