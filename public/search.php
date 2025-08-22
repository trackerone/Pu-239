<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


declare(strict_types = 1);

use Pu239\Database;
use Pu239\Session;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
global $container, $site_config;

$data = array_merge($_GET, $_POST);
$torrent_pass = $data['torrent_pass'];
$auth = $data['auth'];
$bot = $data['bot'];
$search = $data['search'];
if (!empty($bot) && !empty($auth) && !empty($torrent_pass)) {
    $users_class = $container->get(User::class);
    $userid = $users_class->get_bot_id($bot, $torrent_pass, $auth);
} else {
    $session = $container->get(Session::class);
    $session->set('is-warning', _('The search page is a restricted page, bots only'));
    header("Location: {$site_config['paths']['baseurl']}/browse.php");
    app_halt();
}

header('content-type: application/json');
if (empty($userid)) {
    echo json_encode(['msg' => _('invalid user credentials')]);
    app_halt();
}
$status = $users_class->get_item('status', $userid);
if ($status === 5) {
    echo json_encode(['msg' => _("Permission denied, you're account is suspended")]);
    app_halt();
} elseif ($status === 2) {
    echo json_encode(['msg' => _("Permission denied, you're account is disabled")]);
    app_halt();
} elseif ($status === 1) {
    echo json_encode(['msg' => _("Permission denied, you're account is parked")]);
    app_halt();
}
if (!empty($search)) {
    $fluent = $container->get(Database::class);
    $results = $fluent->from('torrents')
                      ->select(null)
                      ->select('id')
                      ->select('name')
                      ->select('hex(info_hash) AS info_hash')
                      ->where('name LIKE ?', "%$search%")
                      ->fetchAll();

    if ($results) {
        echo json_encode($results);
        app_halt();
    } else {
        echo json_encode(['msg' => 'no results for: ' . $search]);
        app_halt();
    }
}
