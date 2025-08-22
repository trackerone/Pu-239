<?php
require_once __DIR__ . '/runtime_safe.php';


declare(strict_types = 1);

use Envms\FluentPDO\Literal;
use Pu239\Database;
use Pu239\Session;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
$user = check_user_status();
global $container, $site_config;

if (empty($_POST['id']) && empty($_GET['id'])) {
    app_halt();
}
$id = !empty($_GET['id']) ? (int) $_GET['id'] : (int) $_POST['id'];
if (!is_valid_id($id)) {
    stderr(_('Error'), _('Invalid ID'), 'bottom20');
}
$fluent = $container->get(Database::class);
$torrent = $fluent->from('torrents')
                  ->select(null)
                  ->select('id')
                  ->select('thanks')
                  ->select('comments')
                  ->where('id = ?', $id)
                  ->fetch();

if (empty($torrent)) {
    stderr(_('Error'), _('Torrent not found'), 'bottom20');
}
$thanks = $fluent->from('thankyou')
                 ->select(null)
                 ->select('tid')
                 ->where('torid = ?', $id)
                 ->where('uid = ?', $user['id'])
                 ->fetch('tid');

if (!empty($thanks)) {
    stderr(_('Error'), 'You have already thanked.', 'bottom20');
}
$text = ':thankyou:';
$values = [
    'uid' => $user['id'],
    'torid' => $id,
    'thank_date' => TIME_NOW,
];
$fluent->insertInto('thankyou')
       ->values($values)
       ->execute();
$values = [
    'user' => $user['id'],
    'torrent' => $id,
    'added' => TIME_NOW,
    'text' => $text,
    'ori_text' => $text,
];
$fluent->insertInto('comments')
       ->values($values)
       ->execute();

$set = [
    'thanks' => new Literal('thanks + 1'),
    'comments' => new Literal('comments + 1'),
];
$fluent->update('torrents')
       ->set($set)
       ->where('id = ?', $id)
       ->execute();

$cache->deleteMulti([
    'latest_comments_',
    'torrent_details_' . $id,
]);
if ($site_config['bonus']['on']) {
    $set = [
        'seedbonus' => new Literal('seedbonus + ' . $site_config['bonus']['per_comment']),
    ];
    $fluent->update('users')
           ->set($set)
           ->where('id = ?', $user['id'])
           ->execute();
}
$session = $container->get(Session::class);
$session->set('is-success', "Your 'Thank you' has been registered!");
header("Refresh: 0; url=details.php?id=$id");
