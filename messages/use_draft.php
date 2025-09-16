<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Message;
use Pu239\User;

$save_or_edit = (isset($_POST['edit']) ? 'edit' : (isset($_GET['edit']) ? 'edit' : 'save'));
$save_or_edit = (isset($_POST['send']) ? 'send' : (isset($_GET['send']) ? 'send' : $save_or_edit));

global $container, $site_config, $CURUSER;

// TODO(2025): csrf
$messages_class = $container->get(Message::class);
$users_class = $container->get(User::class);

if (isset($_POST['buttonval']) && $_POST['buttonval'] === $save_or_edit) {
    if (empty($_POST['subject'])) {
        stderr(_('Error'), _('To save a message in your draft folder, it must have a subject!'));
    }
    if (empty($_POST['body'])) {
        stderr(_('Error'), _('To save a message in your draft folder, it must have body text!'));
    }

    $body = trim($_POST['body']);
    $subject = strip_tags(trim($_POST['subject']));
    $urgent = (isset($_POST['urgent']) && $_POST['urgent'] === 'yes' && $CURUSER['class'] >= UC_STAFF) ? 'yes' : 'no';
    $returnto = isset($_POST['returnto']) ? htmlsafechars($_POST['returnto']) : '';

    if ($save_or_edit === 'save') {
        $values = [[
            'sender' => $CURUSER['id'],
            'receiver' => $CURUSER['id'],
            'added' => TIME_NOW,
            'msg' => $body,
            'subject' => $subject,
            'location' => -2,
            'draft' => 'yes',
            'unread' => 'no',
            'saved' => 'yes',
        ]];
        $result = $messages_class->insert($values);
    } elseif ($save_or_edit === 'edit') {
        $update = [
            'msg' => $body,
            'subject' => $subject,
        ];
        $result = $messages_class->update($update, $pm_id);
    } elseif ($save_or_edit === 'send') {
        $receiver_id = (int) $users_class->getUserIdFromName((string) $_POST['to']);
        if (!$receiver_id) {
            stderr(_('Error'), _('Member not found!'));
        }
        $messages = [[
            'sender' => $CURUSER['id'],
            'poster' => $CURUSER['id'],
            'receiver' => $receiver_id,
            'added' => TIME_NOW,
            'msg' => $body,
            'subject' => $subject,
            'saved' => 'no',
            'urgent' => $urgent,
        ]];
        $result = $messages_class->insert($messages);
        if (!$result) {
            stderr(_('Error'), _("Messages weren't sent!"));
        }
        if ($returnto) {
            header('Location: ' . $returnto);
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_mailbox&sent=1');
        }
        app_halt('Exit called');
    }

    if (!$result) {
        stderr(_('Error'), _("Draft wasn't saved!"));
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_mailbox&box=-2&new_draft=1');
    app_halt('Exit called');
}

if (isset($_POST['buttonval'])) {
    $message = $messages_class->get_by_id($pm_id);
    $subject = htmlsafechars($message['subject']);
    $draft = $message['msg'];
}

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

