<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Envms\FluentPDO\Literal;
use Pu239\Database;
use Pu239\Session;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
$user = check_user_status();
global $container, $site_config;

if (empty($_POST['id']) && empty($_GET['id'])) {
    app_halt('Exit called');
}
$id = !empty($_GET['id']) ? (int) $_GET['id'] : (int) $_POST['id'];
if (!is_valid_id($id)) {
    stderr(_('Error'), _('Invalid ID'), 'bottom20');
}
$fluent = $container->get(Database::class);
$torrent = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

if (empty($torrent)) {
    stderr(_('Error'), _('Torrent not found'), 'bottom20');
}
$thanks = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
$values = [
    'user' => $user['id'],
    'torrent' => $id,
    'added' => TIME_NOW,
    'text' => $text,
    'ori_text' => $text,
];
// TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

$set = [
    'thanks' => new Literal('thanks + 1'),
    'comments' => new Literal('comments + 1'),
];
// TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

$cache->deleteMulti([
    'latest_comments_',
    'torrent_details_' . $id,
]);
if ($site_config['bonus']['on']) {
    $set = [
        'seedbonus' => new Literal('seedbonus + ' . $site_config['bonus']['per_comment']),
    ];
    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
}
$session = $container->get(Session::class);
$session->set('is-success', "Your 'Thank you' has been registered!");
header("Refresh: 0; url=details.php?id=$id");
