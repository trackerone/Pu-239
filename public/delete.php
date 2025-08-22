<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


declare(strict_types = 1);

use Pu239\Database;
use Pu239\Message;
use Pu239\Session;
use Pu239\Torrent;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once CLASS_DIR . 'class_user_options_2.php';
$user = check_user_status();
global $container, $site_config;

$data = array_merge($_GET, $_POST);
if (empty($data['id'])) {
    stderr(_('Error'), _('missing form data'));
}
$id = !empty($data['id']) ? (int) $data['id'] : 0;
if (!is_valid_id($id)) {
    stderr(_('Error'), _('missing form data'));
}
$dt = TIME_NOW;
$fluent = $container->get(Database::class);
$row = $fluent->from('torrents AS t')
              ->select(null)
              ->select('t.id')
              ->select('t.info_hash')
              ->select('t.owner')
              ->select('t.name')
              ->select('t.seeders')
              ->select('t.added')
              ->select('u.seedbonus')
              ->leftJoin('users AS u ON u.id=t.owner')
              ->where('t.id = ?', $id)
              ->fetch();

if (!$row) {
    stderr(_('Error'), _('Torrent does not exist'));
}
if ($user['id'] != $row['owner'] && $user['class'] < UC_STAFF) {
    stderr(_('Error'), _("You're not the owner! How did that happen?"));
}
$rt = (int) $data['reasontype'];
if (!is_int($rt) || $rt < 1 || $rt > 5) {
    stderr(_('Error'), _('Invalid reason'));
}
$reason = $data['reason'];
if ($rt === 1) {
    $reasonstr = _('Dead: 0 seeders and leechers = 0 peers total');
} elseif ($rt === 2) {
    $reasonstr = _('Dupe') . ($reason[0] ? (': ' . trim($reason[0])) : '!');
} elseif ($rt === 3) {
    $reasonstr = _('Nuked') . ($reason[1] ? (': ' . trim($reason[1])) : '!');
} elseif ($rt === 4) {
    if (!$reason[2]) {
        stderr(_('Error'), _('Please describe the violated rule.'));
    }
    $reasonstr = $site_config['site']['name'] . _(' rules broken: ') . trim($reason[2]);
} else {
    if (!$reason[3]) {
        stderr(_('Error'), _('Please enter the reason for deleting this torrent.'));
    }
    $reasonstr = trim($reason[3]);
}
$torrents_class = $container->get(Torrent::class);
$torrents_class->delete_by_id($row['id']);
$torrents_class->remove_torrent($row['info_hash']);

write_log(_fe('Torrent {0} ({1}) was deleted by {2} ({3})', $id, $row['name'], $user['username'], $reasonstr));
if ($site_config['bonus']['on']) {
    $user_class = $container->get(User::class);
    $dt = sqlesc($dt - (14 * 86400));
    if ($row['added'] > $dt) {
        $owner = $user_class->getUserFromId($row['owner']);
        if (!empty($owner)) {
            $update = [
                'seedbonus' => $owner['seedbonus'] - $site_config['bonus']['per_delete'],
            ];
            $user_class->update($update, $owner['id']);
        }
    }
}
$msg = _fe('Torrent {0} ({2}) has been deleted.<br><br>Reason: {2}', $id, htmlsafechars($row['name']), $reasonstr);
if ($user['id'] != $row['owner'] && ($user['opt2'] & class_user_options_2::PM_ON_DELETE) === class_user_options_2::PM_ON_DELETE) {
    $subject = 'Torrent Deleted';
    $msgs_buffer[] = [
        'receiver' => $row['owner'],
        'added' => $dt,
        'msg' => $msg,
        'subject' => $subject,
    ];
    $messages_class = $container->get(Message::class);
    $messages_class->insert($msgs_buffer);
}
$session = $container->get(Session::class);
$session->set('is-success', $msg);
if (!empty($data['returnto'])) {
    header('Location: ' . htmlsafechars($data['returnto']));
} else {
    header("Location: {$site_config['paths']['baseurl']}/browse.php");
}
