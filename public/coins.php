<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

use Pu239\Cache;
use Pu239\Message;
use Pu239\Session;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
$user = check_user_status();
global $container;
$db = $container->get(Database::class);, $site_config;

$id = (int) $_GET['id'];
$points = (int) $_GET['points'];
$dt = TIME_NOW;
if (!is_valid_id($id) || !is_valid_id($points)) {
    app_halt('Exit called');
}
$pointscangive = [
    10,
    20,
    50,
    100,
    200,
    500,
    1000,
];
$returnto = "details.php?id=$id";
$session = $container->get(Session::class);
if (!in_array($points, $pointscangive)) {
    $session->set('is-warning', _("You can't give that amount of points!"));
    header("Location: $returnto");
    app_halt('Exit called');
}
$sdsa = $db->run(');
$db->run('UPDATE users SET seedbonus=seedbonus+' . sqlesc($points) . ' WHERE id = :id', [':id' => $userid]) or sqlerr(__FILE__, __LINE__);
$db->run('UPDATE users SET seedbonus=seedbonus-' . sqlesc($points) . ' WHERE id=' . sqlesc($user['id'])) or sqlerr(__FILE__, __LINE__);
$db->run(');
$msgs_buffer[] = [
    'receiver' => $userid,
    'added' => $dt,
    'msg' => $msg,
    'subject' => $subject,
];
$messages_class = $container->get(Message::class);
$messages_class->insert($msgs_buffer);
$update['points'] = ($row['points'] + $points);
$update['seedbonus_uploader'] = ($User['seedbonus'] + $points);
$update['seedbonus_donator'] = ($user['seedbonus'] - $points);
$cache = $container->get(Cache::class);
//==The torrent
$cache->update_row('torrent_details_' . $id, [
    'points' => $update['points'],
], $site_config['expires']['torrent_details']);
//==The uploader
$cache->update_row('user_' . $userid, [
    'seedbonus' => $update['seedbonus_uploader'],
], $site_config['expires']['user_cache']);
//==The donator
$cache->update_row('user_' . $user['id'], [
    'seedbonus' => $update['seedbonus_donator'],
], $site_config['expires']['user_cache']);
//== delete the pm keys
$cache->delete('coin_points_' . $id);

$session->set('is-success', _('Successfully gave points to this torrent.'));
header("Location: $returnto");
app_halt('Exit called');
