<?php
declare(strict_types=1);

use Pu239\Database;
use Pu239\Session;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_account_delete.php';
require_once CLASS_DIR . 'class_check.php';

global $container, $site_config, $CURUSER;

/** @var Database $db */
$db = $container->get(Database::class);
/** @var Session $session */
$session = $container->get(Session::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$HTMLOUT = '';
$record_mail = true;
$days = 30;

// Threshold for inactivity
$threshold = TIME_NOW - ($days * 86400);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
    $ids = isset($_POST['userid']) && is_array($_POST['userid']) ? array_values(array_unique(array_map('intval', $_POST['userid']))) : [];

    if (empty($ids) && ($action === 'deluser' || $action === 'mail' || $action === 'disable')) {
        $session->set('is-warning', _('For this to work you must select at least a user!'));
    } else {
        if ($action === 'deluser') {
            if (!has_access((int) $CURUSER['class'], UC_ADMINISTRATOR, 'coder')) {
                $session->set('is-warning', _('You do not have permission to delete users.'));
            } else {
                // Get usernames for logging
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $users = $db->fetchAll("SELECT id, username FROM users WHERE id IN ($placeholders)", $ids);
                    foreach ($users as $u) {
                        $userid = (int) $u['id'];
                        $username = (string) $u['username'];
                        if (account_delete($userid)) {
                            write_log("User: " . htmlsafechars($username) . " was deleted by {$CURUSER['username']}");
                        }
                    }
                    $session->set('is-success', _('You have successfully deleted the selected accounts!'));
                }
            }
        } elseif ($action === 'disable') {
            if (!empty($ids)) {
                $reason = _fe('Disabled for inactivity by {0} on {1}', $CURUSER['username'], get_date(TIME_NOW, 'DATE', 1));
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $params = array_merge([$reason], $ids);
                $db->run("UPDATE users SET status = 2, disable_reason = ? WHERE id IN ($placeholders)", $params);
                $session->set('is-success', _('You have successfully disabled the selected accounts!'));
            }
        } elseif ($action === 'mail') {
            if (!empty($ids)) {
                // Fetch selected users
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $rows = $db->fetchAll(
                    "SELECT id, email, username, registered, last_access
                     FROM users
                     WHERE id IN ($placeholders)
                     ORDER BY last_access DESC",
                    $ids
                );

                $success = 0;
                foreach ($rows as $arr) {
                    $id = (int) $arr['id'];
                    $username = htmlsafechars((string) $arr['username']);
                    $added = get_date((int) $arr['registered'], 'DATE');
                    $last_access = get_date((int) $arr['last_access'], 'DATE');
                    $body = doc_head(_('Your account at ')) . '
</head>
<body>
<p>' . _('Hey') . " $username,</p>
<p>" . _('Your account at ') . " {$site_config['site']['name']} " . _(' has been marked as inactive and will be deleted. If you wish to remain a member at') . " {$site_config['site']['name']}" . _(', please login.') . '<br>
' . _('Your username is: ') . " $username<br>
" . _('And was created: ') . " $added<br>
" . _('Last accessed: ') . " $last_access<br>
" . _('Login at: ') . " {$site_config['paths']['baseurl']}/login.php<br>
" . _('If you have forgotten your password you can retrieve it at') . " {$site_config['paths']['baseurl']}/resetpw.php<br>
" . _('Welcome back!') . " {$site_config['site']['name']}</p>
</body>
</html>";
                    $mail = send_mail(
                        (string) $arr['email'],
                        _('Your account at ') . "{$site_config['site']['name']}!",
                        $body,
                        strip_tags($body)
                    );
                    if ($mail) {
                        ++$success;
                    }
                }

                if ($record_mail) {
                    $date = TIME_NOW;
                    $actor = (int) $CURUSER['id'];
                    // Upsert last mail record into avps
                    $db->run(
                        "INSERT INTO avps (arg, value_s, value_i, value_u)
                         VALUES (:arg, :vs, :vi, :vu)
                         ON DUPLICATE KEY UPDATE value_s = VALUES(value_s), value_i = VALUES(value_i), value_u = VALUES(value_u)",
                        [
                            ':arg' => 'inactivemail',
                            ':vs'  => (string) $actor,
                            ':vi'  => (int) $date,
                            ':vu'  => (int) $success,
                        ]
                    );
                }

                if ($success > 0) {
                    $session->set('is-success', _fe('Successfully sent {0} email(s).', $success));
                } else {
                    $session->set('is-warning', _('No emails were sent.'));
                }
            }
        }
    }
}

// Count inactive
$countRow = $db->fetch(
    'SELECT COUNT(id) AS count FROM users WHERE last_access < :th AND verified = 1 AND status = 0',
    [':th' => $threshold]
);
$count = (int) ($countRow['count'] ?? 0);

$perpage = 15;
$pager = pager($perpage, $count, $site_config['paths']['baseurl'] . '/staffpanel.php?tool=inactive&amp;');

// Fetch inactive page
$rows = [];
if ($count > 0) {
    $rows = $db->fetchAll(
        'SELECT id, username, class, email, uploaded, downloaded, last_access
         FROM users
         WHERE last_access < :th AND verified = 1 AND status = 0
         ORDER BY last_access DESC ' . $pager['limit'],
        [':th' => $threshold]
    );
}

if ($count > 0) {
    if ($count > $perpage) {
        $HTMLOUT .= $pager['pagertop'];
    }

    $HTMLOUT .= "<script>
    /*<![CDATA[*/
    var checkflag = 'false';
    function check(nodeList) {
        if (!nodeList) return 'Check All';
        var len = nodeList.length || 0;
        if (checkflag == 'false') {
            for (var i = 0; i < len; i++) { nodeList[i].checked = true; }
            checkflag = 'true';
            return 'Uncheck All';
        } else {
            for (var i = 0; i < len; i++) { nodeList[i].checked = false; }
            checkflag = 'false';
            return 'Check All';
        }
    }
    /*]]>*/
    </script>";

    $HTMLOUT .= "
    <div class='row'><div class='col-md-12'>
    <h1 class='has-text-centered'>" . _fe('{0} accounts inactive for longer than {1} days.', $count, $days) . "</h1>
    <form method='post' action='staffpanel.php?tool=inactive&amp;action=inactive' enctype='multipart/form-data' accept-charset='utf-8'>
    <table class='table table-bordered'>
    <tr>
        <td class='colhead'>" . _('Username') . "</td>
        <td class='colhead'>" . _('Class') . "</td>
        <td class='colhead'>" . _('Email') . "</td>
        <td class='colhead'>" . _('Ratio') . "</td>
        <td class='colhead'>" . _('Last seen') . "</td>
        <td class='colhead'>" . _('X') . "</td>
    </tr>";

    foreach ($rows as $arr) {
        $ratio = member_ratio((float) $arr['uploaded'], (float) $arr['downloaded']);
        $last_seen = ((int) $arr['last_access'] === 0) ? 'never' : get_date((int) $arr['last_access'], 'DATE');
        $class_name = get_user_class_name((int) $arr['class']);
        $HTMLOUT .= '<tr>
            <td>' . format_username((int) $arr['id']) . '</td>
            <td>' . $class_name . "</td>
            <td style='max-width:130px;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;'><a href='mailto:" . htmlsafechars((string) $arr['email']) . "'>" . htmlsafechars((string) $arr['email']) . '</a></td>
            <td>' . $ratio . '</td>
            <td>' . $last_seen . "</td>
            <td><input type='checkbox' name='userid[]' value='" . (int) $arr['id'] . "'></td>
        </tr>";
    }

    $HTMLOUT .= "<tr>
        <td colspan='6' class='colhead'>
            <select name='action'>
                <option value='mail'>" . _('Send email') . "</option>
                <option value='deluser' " . (!has_access((int) $CURUSER['class'], UC_ADMINISTRATOR, 'coder') ? 'disabled' : '') . '>' . _('Delete users') . "</option>
                <option value='disable'>" . _('Disable accounts') . "</option>
            </select>
            &#160;&#160;
            <input type='submit' name='submit' value='" . _('Apply Changes') . "' class='button is-small'>
            &#160;&#160;
            <input type='button' value='Check all' onclick='this.value=check(document.getElementsByName(\"userid[]\"))' class='button is-small'>
        </td>
    </tr>";

    if ($record_mail) {
        $dateRow = $db->fetch(
            "SELECT avps.value_s AS userid, avps.value_i AS last_mail, avps.value_u AS mails, users.username
               FROM avps
               LEFT JOIN users ON avps.value_s = users.id
              WHERE avps.arg = 'inactivemail'
              LIMIT 1"
        );
        if (!empty($dateRow) && (int) ($dateRow['last_mail'] ?? 0) > 0) {
            $HTMLOUT .= "<tr><td colspan='6' class='colhead has-text-danger'>" . _pfe(
                'Last Email sent by {1} on {2} - {0} email sent',
                'Last Email sent by {1} on {2} - {0} emails sent',
                (int) $dateRow['mails'],
                format_username((int) ($dateRow['userid'] ?? 0)),
                get_date((int) $dateRow['last_mail'], 'DATE')
            ) . '</td></tr>';
        }
    }

    $HTMLOUT .= '</table></form>';
    $HTMLOUT .= '</div></div>';

    if ($count > $perpage) {
        $HTMLOUT .= $pager['pagerbottom'];
    }
} else {
    $HTMLOUT .= stdmsg(_('Awesome'), _fe('No account inactive for longer than {0} days.', $days));
}

$title = _('Inactive Users');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
