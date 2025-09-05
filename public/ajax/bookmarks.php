<?php
require_once __DIR__ . '/../../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Database;

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();
global $container;

$private = $_POST['private'];
$remove = $_POST['remove'];
$tid = $_POST['tid'];
header('content-type: application/json');
if (empty($tid)) {
    echo json_encode(['fail' => 'invalid']);
    app_halt('Exit called');
}
if (empty($user)) {
    echo json_encode(['fail' => 'csrf']);
    app_halt('Exit called');
}
$fluent = $container->get(Database::class);
$cache = $container->get(Cache::class);
if ($private === 'true') {
    $bookmark = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    $cache->delete('bookmarks_' . $user['id']);
    echo json_encode([
        'bookmark' => $private,
        'content' => 'private',
        'text' => $text,
        'tid' => $tid,
        'remove' => 'false',
    ]);
    app_halt('Exit called');
}

$bookmark = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
    $cache->delete('bookmarks_' . $user['id']);
    echo json_encode([
        'content' => 'deleted',
        'text' => _('Add Bookmark'),
        'tid' => $tid,
        'remove' => $remove,
    ]);
    app_halt('Exit called');
} else {
    $values = [
        'userid' => $user['id'],
        'torrentid' => $tid,
    ];
    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
    $cache->delete('bookmarks_' . $user['id']);
    echo json_encode([
        'content' => 'added',
        'text' => _('Delete Bookmark'),
        'tid' => $tid,
        'remove' => $remove,
    ]);
    app_halt('Exit called');
}
