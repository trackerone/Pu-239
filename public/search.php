<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;
use Pu239\User;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);
/** @var User $users_class */
$users_class = $container->get(User::class);

require_once __DIR__ . '/../include/bittorrent.php';

$data = array_merge($_GET, $_POST);
$torrent_pass = $data['torrent_pass'];
$auth = $data['auth'];
$bot = $data['bot'];
$search = $data['search'];
if (!empty($bot) && !empty($auth) && !empty($torrent_pass)) {
    $userid = $users_class->get_bot_id($bot, $torrent_pass, $auth);
} else {
    /** @var Session $session */
    $session = $container->get(Session::class);
    $session->set('is-warning', _('The search page is a restricted page, bots only'));
    $baseUrl = (string) $config->get('paths.baseurl');
    header("Location: {$baseUrl}/browse.php");
    app_halt('Exit called');
}

if (empty($userid)) {
    json_out(['msg' => _('invalid user credentials')]);
}
$status = $users_class->get_item('status', $userid);
if ($status === 5) {
    json_out(['msg' => _("Permission denied, you're account is suspended")]);
} elseif ($status === 2) {
    json_out(['msg' => _("Permission denied, you're account is disabled")]);
} elseif ($status === 1) {
    json_out(['msg' => _("Permission denied, you're account is parked")]);
}
if (!empty($search)) {
    // $fluent removed — use $this->db (ExtendedPdo)
    $results = $fluent->from('torrents')
                      ->select(null)
                      ->select('id')
                      ->select('name')
                      ->select('hex(info_hash) AS info_hash')
                      ->where('name LIKE ?', "%$search%")
                      ->fetchAll();

    if ($results) {
        json_out($results);
    }

    json_out(['msg' => 'no results for: ' . $search]);
}
