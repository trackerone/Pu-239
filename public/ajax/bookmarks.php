<?php

declare(strict_types=1);

use Pu239\Cache;
use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$cache = $container->get(Cache::class);
$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();

if ($user === false) {

    json_out(['fail' => 'csrf']);
}

// TODO(2025): csrf
$torrentId = (int) ($_POST['tid'] ?? 0);
$togglePrivate = ($_POST['private'] ?? '') === 'true';
$remove = $_POST['remove'] ?? 'false';

if ($torrentId <= 0) {

    json_out(['fail' => 'invalid']);
}

$bindings = [
    'torrentId' => $torrentId,
    'userId' => (int) $user['id'],
];

if ($togglePrivate) {
    $bookmark = $db->row(
        'SELECT private FROM bookmarks WHERE torrentid = :torrentId AND userid = :userId',
        $bindings
    );

    if ($bookmark === null) {

        json_out(['fail' => 'missing']);
    }

    $current = $bookmark['private'] === 'yes';
    $nextValue = $current ? 'no' : 'yes';
    $label = $current ? _('Mark Bookmark Private!') : _('Mark Bookmark Public!');

    $db->run(
        'UPDATE bookmarks SET private = :private WHERE torrentid = :torrentId AND userid = :userId',
        $bindings + ['private' => $nextValue]
    );

    $cache->delete('bookmarks_' . $user['id']);

    json_out([
        'bookmark' => $nextValue,
        'content' => 'private',
        'text' => $label,
        'tid' => $torrentId,
        'remove' => 'false',
    ]);
}

$bookmark = $db->row(
    'SELECT id FROM bookmarks WHERE torrentid = :torrentId AND userid = :userId',
    $bindings
);

if ($bookmark !== null) {
    $db->run('DELETE FROM bookmarks WHERE id = :id', ['id' => (int) $bookmark['id']]);
    $cache->delete('bookmarks_' . $user['id']);

    json_out([
        'content' => 'deleted',
        'text' => _('Add Bookmark'),
        'tid' => $torrentId,
        'remove' => $remove,
    ]);
}

$db->run(
    'INSERT INTO bookmarks (userid, torrentid, private, added) VALUES (:userId, :torrentId, :private, :added)',
    [
        'userId' => (int) $user['id'],
        'torrentId' => $torrentId,
        'private' => 'no',
        'added' => [TIME_NOW, \PDO::PARAM_INT],
    ]
);

$cache->delete('bookmarks_' . $user['id']);

json_out([
    'content' => 'added',
    'text' => _('Delete Bookmark'),
    'tid' => $torrentId,
    'remove' => $remove,
]);
