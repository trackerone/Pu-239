<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

require_once INCL_DIR . 'function_html.php';

$save_or_edit = (isset($_POST['edit']) ? 'edit' : (isset($_GET['edit']) ? 'edit' : 'save'));
$save_or_edit = (isset($_POST['send']) ? 'send' : (isset($_GET['send']) ? 'send' : $save_or_edit));
global $contianer, $site_config, $CURUSER;

if (isset($_POST['buttonval']) && $_POST['buttonval'] == $save_or_edit) {
    if (empty($_POST['subject'])) {
        stderr(_('Error'), _('To save a message in your draft folder, it must have a subject!'));
    }
    if (empty($_POST['body'])) {
        stderr(_('Error'), _('To save a message in your draft folder, it must have body text!'));
    }

    $body = sqlesc(trim($_POST['body']));
    $subject = sqlesc(strip_tags(trim($_POST['subject'])));
    $urgent = sqlesc((isset($_POST['urgent']) && $_POST['urgent'] === 'yes' && $CURUSER['class'] >= UC_STAFF) ? 'yes' : 'no');
    if ($save_or_edit === 'save') {
        $db->run('INSERT INTO messages (sender, receiver, added, msg, subject, location, draft, unread, saved) VALUES (' . sqlesc($CURUSER['id']) . ', ' . sqlesc($CURUSER['id']) . ',' . TIME_NOW . ', ' . $body . ', ' . $subject . ', \'-2\', \'yes\',\'no\',\'yes\')') or sqlerr(__FILE__, __LINE__);
    } elseif ($save_or_edit === 'edit') {
        $db->run('UPDATE messages SET msg = ' . $body . ', subject = ' . $subject . ' WHERE id = :id', [':id' => $pm_id]) or sqlerr(__FILE__, __LINE__);
    } elseif ($save_or_edit === 'send') {
        $res_receiver = $db->run(');
        $cache->increment('inbox_' . $receiver);
        $cache->increment('messages_count_' . $receiver);
        if (mysqli_affected_rows($mysqli) === 0) {
            stderr(_('Error'), _("Messages weren't sent!"));
        }

        if (strpos($arr_receiver['notifs'], '[pm]') !== false) {
            $username = htmlsafechars($CURUSER['username']);
            $title = $site_config['site']['name'];
            $body = doc_head("{$title} PM received") . '
</head>
<body>
<p>' . _('You have received a PM from %s!', $username) . '</p>
<p>' . _('You can use the URL below to view the message (you may have to login).') . "</p>
<p>{$site_config['paths']['baseurl']}/messages.php</p>
<p>--{$site_config['site']['name']}</p>
</body>
</html>";

            send_mail($arr_receiver['email'], _('You have received a PM from %s!', $username), $body, strip_tags($body));
        }
        if ($returnto) {
            header('Location: ' . $returnto);
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_mailbox&sent=1');
        }
        app_halt('Exit called');
    }
    if (mysqli_affected_rows($mysqli) === 0) {
        stderr(_('Error'), _("Draft wasn't saved!"));
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_mailbox&box=-2&new_draft=1');
    app_halt('Exit called');
}

if (isset($_POST['buttonval'])) {
    //=== Get the info
    $res = sql_query('SELECT * FROM messages WHERE id = ' . sqlesc($pm_id)) or sqlerr(__FILE__, __LINE__);
    $message = mysqli_fetch_assoc($res);
    $subject = htmlsafechars($message['subject']);
    $draft = $message['msg'];
}
//=== print out the page
$HTMLOUT .= '<h1>' . _('Use Draft: ') . $subject . '</h1>' . $top_links . '
        <form name="compose" action="messages.php" method="post" accept-charset="utf-8">
        <input type="hidden" name="id" value="' . $pm_id . '">
        <input type="hidden" name="' . $save_or_edit . '" value="1">
        <input type="hidden" name="action" value="use_draft">
    <table class="table table-bordered">
    <tr>
        <td class="colhead" colspan="2">' . _('use draft') . '</td>
    </tr>
    <tr>
        <td><span style="font-weight: bold;">' . _('To:') . '</span></td>
        <td><input type="text" name="to" value="' . ((isset($_POST['to']) && valid_username($_POST['to'], false)) ? htmlsafechars($_POST['to']) : _('Enter Username')) . '" class="member" onfocus="this.value=\'\';">
         ' . _('[ enter the username of the member you would like to send this to ]') . '</td>
    </tr>
    <tr>
        <td><span style="font-weight: bold;">' . _('Subject:') . '</span></td>
        <td><input type="text" class="w-100" name="subject" value="' . $subject . '"></td>
    </tr>
    <tr>
        <td><span style="font-weight: bold;">' . _('Body:') . '</span></td>
        <td class="is-paddingless">' . BBcode($draft) . '</td>
    </tr>
    <tr>
        <td colspan="2">' . ($CURUSER['class'] >= UC_STAFF ? '
        <input type="checkbox" name="urgent" value="yes" ' . ((isset($_POST['urgent']) && $_POST['urgent'] === 'yes') ? 'checked' : '') . '> 
        <span style="font-weight: bold;color:red;">' . _('Mark as URGENT!') . '</span>' : '') . '
        <input type="submit" class="button is-small" name="buttonval" value="' . $save_or_edit . '"></td>
    </tr>
    </table></form>';
