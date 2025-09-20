<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Message;
use Pu239\User;

global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);

// TODO(2025): csrf

flood_limit('messages');
$messages_class = $container->get(Message::class);
$message = $messages_class->get_by_id($pm_id);
if (empty($message)) {
    stderr(_('Error'), _('Message Not Found!'));
}
if ($message['receiver'] == $CURUSER['id'] && $message['sender'] == $CURUSER['id']) {
    stderr(_('Error'), _('He be as good a gentleman as the devil is, as Lucifer and Beelzebub himself.'));
}
$users_class = $container->get(User::class);
$to_name = isset($_POST['to']) ? trim((string) $_POST['to']) : '';
$to_user = $users_class->getUserFromId((int) $users_class->getUserIdFromName($to_name));
if (empty($to_user)) {
    stderr(_('Error'), _('Sorry, there is no member with that username.'));
}

$count = $messages_class->get_count($to_user['id'], 1, false);
if ($count > ($maxbox * 6) && !has_access($CURUSER['class'], UC_STAFF, '')) {
    stderr(_('Sorry'), _('Members mailbox is full.'));
}

if ($CURUSER['status'] === 5) {
    if (!has_access($to_user['class'], UC_STAFF, '')) {
        stderr(_('Error'), _('Your account is suspended, you may only forward PMs to staff!'));
    }
}

if (!has_access($CURUSER['class'], UC_STAFF, '')) {
    if ($to_user['acceptpms'] === 'no') {
        stderr(_('Error'), _("This user dosen't accept PMs."));
    }
    $blocked = $db->fetch(
        'SELECT id FROM blocks WHERE userid = :userid AND blockid = :blockid',
        [
            'userid' => (int) $to_user['id'],
            'blockid' => (int) $CURUSER['id'],
        ],
    );
    if ($blocked) {
        stderr(_('Refused'), _('This member has blocked PMs from you.'));
    }
    if ($to_user['acceptpms'] === 'friends') {
        $friend = $db->fetch(
            'SELECT id FROM friends WHERE userid = :userid AND friendid = :friendid',
            [
                'userid' => (int) $to_user['id'],
                'friendid' => (int) $CURUSER['id'],
            ],
        );
        if (!$friend) {
            stderr(_('Refused'), _('This member only accepts PMs from members on their friends list.'));
        }
    }
}

$subject = isset($_POST['subject']) ? htmlsafechars((string) $_POST['subject']) : '';
$first_from_input = isset($_POST['first_from']) ? (string) $_POST['first_from'] : '';
$first_from = valid_username($first_from_input) ? htmlsafechars($first_from_input) : '';
$body_content = isset($_POST['body']) ? (string) $_POST['body'] : '';
$msg = "\n\n" . $body_content . "\n\n" . _fe("-------- Original Message from [b]{0} :: [/b]{1}\n{3}", $first_from, htmlsafechars($message['subject']), $message['msg']);

$msgs_buffer[] = [
    'sender' => $CURUSER['id'],
    'poster' => $CURUSER['id'],
    'receiver' => $to_user['id'],
    'added' => TIME_NOW,
    'msg' => $msg,
    'subject' => $subject,
    'saved' => $save,
    'urgent' => $urgent,
];
$result = $messages_class->insert($msgs_buffer);
if (!$result) {
    stderr(_('Error'), _("Message couldn't be forwarded!"));
}

if (strpos($to_user['notifs'], '[pm]') !== false) {
    $username = htmlsafechars($CURUSER['username']);
    $title = (string) $config->get('site.name');
    $body = doc_head("{$title} PM received") . '
</head>
<body>
<p>' . _fe('You have received a PM from %s!', $username) . '</p>
<p>' . _('You can use the URL below to view the message (you may have to login).') . "</p>
<p>{$config->get('paths.baseurl')}/messages.php</p>
<p>--{$config->get('site.name')}</p>
</body>
</html>";

    send_mail($to_user['email'], _fe('You have received a PM from {0}!', $username), $body, strip_tags($body));
}
header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_mailbox&forwarded=1');
app_halt('Exit called');
