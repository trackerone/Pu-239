<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Cache;
use Pu239\Database;
use Pu239\Session;
use Pu239\Torrent;
global $container;
$db = $container->get(Database::class);

require_once __DIR__ . '/../include/bittorrent.php';
global $site_config;
$user = check_user_status();

if ($user['class'] < UC_STAFF) {
    stderr(_('Error'), _('You do not have permission to do this.'));
}

if (!isset($_GET['id']) || !is_valid_id((int) $_GET['id'])) {
    stderr(_('Error'), _('Invalid ID'));
}

$id = (int) $_GET['id'];
$tid = $db->fetch(
    'SELECT t.id, t.info_hash, t.owner, t.name, t.added, u.seedbonus
        FROM torrents AS t
        LEFT JOIN users AS u ON u.id = t.owner
        WHERE t.id = :id',
    [':id' => $id]
);

if (!$tid) {
    stderr(_('Error'), _('Something went wrong!'));
}

$sure = isset($_GET['sure']) && (int) $_GET['sure'];
if (!$sure) {
    $returnto = !empty($_GET['returnto']) ? '&amp;returnto=' . urlencode($_GET['returnto']) : '';
    stderr(_('Sanity Check'), _fe('Are you sure you want to delete this torrent?<br>Click {0}here{1} if you are.', "<a href='{$site_config['paths']['baseurl']}/fastdelete.php?id={$_GET['id']}&sure=1{$returnto}' class='is-link'>", '</a>'));
}

$torrents_class = $container->get(Torrent::class);
$torrents_class->remove_torrent($tid['info_hash']);
$torrents_class->delete_by_id($tid['id']);
if ($user['id'] != $tid['owner']) {
    $msg = _fe('Your upload {0} has been deleted by {1}', "[b]{$tid['name']}[/b]", $user['username']);
    $db->run(
        'INSERT INTO messages (sender, receiver, added, msg) VALUES (2, :receiver, :added, :msg)',
        [
            ':receiver' => (int) $tid['owner'],
            ':added' => TIME_NOW,
            ':msg' => $msg,
        ]
    );
}
write_log(_fe('Torrent {0} was deleted by {1}', $tid['name'], $user['username']));
$cache = $container->get(Cache::class);
if ($site_config['bonus']['on']) {
    $dt = TIME_NOW - (14 * 86400);
    if ($tid['added'] > $dt) {
        $sb = $tid['seedbonus'] - $site_config['bonus']['per_delete'];
        $db->run(
            'UPDATE users SET seedbonus = :seedbonus WHERE id = :id',
            [
                ':seedbonus' => $sb,
                ':id' => (int) $tid['owner'],
            ]
        );
        $cache->update_row('user_' . $tid['owner'], [
            'seedbonus' => $sb,
        ], $site_config['expires']['user_cache']);
    }
}
$session = $container->get(Session::class);
$session->set('is-success', _fe("Torrent deleted\n[b]{0}[/b]", format_comment($tid['name'])));
if (isset($_GET['returnto'])) {
    header("Location: {$site_config['paths']['baseurl']}{$_GET['returnto']}");
} else {
    header("Location: {$site_config['paths']['baseurl']}");
}
