<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Message;
use Pu239\Session;

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();
$pm_what = isset($_POST['pm_what']) && $_POST['pm_what'] === 'last10' ? 'last10' : 'owner';
$reseedid = (int) $_POST['reseedid'];
$uploader = (int) $_POST['uploader'];
$name = $_POST['name'];

// TODO(2025): add CSRF verification
global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);

$baseUrl = (string) $config->get('paths.baseurl');
$torrentDetailsTtl = (int) $config->get('expires.torrent_details');
$userCacheTtl = (int) $config->get('expires.user_cache');
$bonusEnabled = (bool) $config->get('bonus.on');

$dt = TIME_NOW;
$subject = 'Request reseed!';
$msg = "@{$user['username']} asked for a reseed on [url={$baseUrl}/details.php?id={$reseedid}][class=has-text-success]{$name}[/class][/url]![br][br]Thank You!";
$msgs_buffer = [];
if ($pm_what === 'last10') {
    $rows = $db->fetchAll('SELECT s.userid, s.torrentid FROM snatched AS s WHERE s.torrentid =' . sqlesc($reseedid) . " AND s.seeder = 'yes' LIMIT 10") or sqlerr(__FILE__, __LINE__);
    while ($row = mysqli_fetch_assoc($res)) {
        $msgs_buffer[] = [
            'receiver' => $row['userid'],
            'added' => $dt,
            'msg' => $msg,
            'subject' => $subject,
        ];
    }
} elseif ($pm_what === 'owner') {
    $msgs_buffer[] = [
        'receiver' => $uploader,
        'added' => $dt,
        'msg' => $msg,
        'subject' => $subject,
    ];
}

$session = $container->get(Session::class);
if (count($msgs_buffer) > 0) {
    $messages_class = $container->get(Message::class);
    $messages_class->insert($msgs_buffer);
    $session->set('is-success', 'PM was sent! Now wait for a seeder!');
} else {
    $session->set('is-warning', 'There were no users to PM!');
}
$db->run('UPDATE torrents SET last_reseed = ' . $dt . ' WHERE id = :id', [':id' => $reseedid]) or sqlerr(__FILE__, __LINE__);
$cache = $container->get(Cache::class);
$cache->update_row('torrent_details_' . $reseedid, [
    'last_reseed' => $dt,
], $torrentDetailsTtl);
if ($bonusEnabled) {
    sql_query('UPDATE users SET seedbonus = seedbonus-10.0 WHERE id=' . sqlesc($user['id'])) or sqlerr(__FILE__, __LINE__);
    $update['seedbonus'] = ($user['seedbonus'] - 10);
    $cache->update_row('user_' . $user['id'], [
        'seedbonus' => $update['seedbonus'],
    ], $userCacheTtl);
}

header("Refresh: 0; url={$baseUrl}/details.php?id=$reseedid");
