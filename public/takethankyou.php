<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();

$bonusEnabled = (bool) $config->get('bonus.on');
$bonusPerComment = (float) $config->get('bonus.per_comment');

if (empty($_POST['id']) && empty($_GET['id'])) {
    app_halt('Exit called');
}
$id = !empty($_GET['id']) ? (int) $_GET['id'] : (int) $_POST['id'];
if (!is_valid_id($id)) {
    stderr(_('Error'), _('Invalid ID'), 'bottom20');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO(2025): add CSRF verification
}
// $fluent removed — use $this->db (ExtendedPdo)
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
$sql = "INSERT INTO thankyou (/* columns */) VALUES (/* values */)";
$db->perform($sql, $values);
$values = [
    'user' => $user['id'],
    'torrent' => $id,
    'added' => TIME_NOW,
    'text' => $text,
    'ori_text' => $text,
];
$sql = "INSERT INTO comments (/* columns */) VALUES (/* values */)";
$db->perform($sql, $values);

$set = [
    'thanks' => new Literal('thanks + 1'),
    'comments' => new Literal('comments + 1'),
];
$sql = "UPDATE torrents SET /* columns */ WHERE id = :id";
$db->perform($sql, array_merge($set, ['id' => $id]));

$cache->deleteMulti([
    'latest_comments_',
    'torrent_details_' . $id,
]);
if ($bonusEnabled) {
    $set = [
        'seedbonus' => new Literal('seedbonus + ' . $bonusPerComment),
    ];
    $sql = "UPDATE users SET /* columns */ WHERE id = :id";
$db->perform($sql, array_merge($set, ['id' => $user['id']]));
}
$session = $container->get(Session::class);
$session->set('is-success', "Your 'Thank you' has been registered!");
header("Refresh: 0; url=details.php?id=$id");
