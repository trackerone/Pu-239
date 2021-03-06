<?php

declare(strict_types = 1);

use Delight\Auth\Auth;
use Pu239\Cache;
use Pu239\Database;

require_once __DIR__ . '/../../include/bittorrent.php';
$lang = load_language('bookmark');
global $container;
$private = false;
extract($_POST);

header('content-type: application/json');
if (empty($tid)) {
    echo json_encode(['fail' => 'invalid']);
    die();
}
$auth = $container->get(Auth::class);
$current_user = $auth->getUserId(); if (empty($current_user)) {
    echo json_encode(['fail' => 'csrf']);
    die();
}
$fluent = $container->get(Database::class);
$cache = $container->get(Cache::class);
if ($private === 'true') {
    $bookmark = $fluent->from('bookmarks')
        ->select(null)
        ->select('private')
        ->where('torrentid = ?', $tid)
        ->where('userid = ?', $current_user)
        ->fetch('private');

    if ($bookmark === 'yes') {
        $private = 'no';
        $text = $lang['bookmarks_private2'];
    } else {
        $private = 'yes';
        $text = $lang['bookmarks_public2'];
    }
    $set = [
        'private' => $private,
    ];

    $fluent->update('bookmarks')
        ->set($set)
        ->where('torrentid = ?', $tid)
        ->where('userid = ?', $current_user)
        ->execute();

    $cache->delete('bookmarks_' . $current_user);
    echo json_encode([
        'bookmark' => $private,
        'content' => 'private',
        'text' => $text,
        'tid' => $tid,
        'remove' => 'false',
    ]);
    die();
}

$bookmark = $fluent->from('bookmarks')
    ->select(null)
    ->select('id')
    ->where('torrentid = ?', $tid)
    ->where('userid = ?', $current_user)
    ->fetch('id');

if (!empty($bookmark)) {
    $fluent->delete('bookmarks')
        ->where('id = ?', $bookmark)
        ->execute();
    $cache->delete('bookmarks_' . $current_user);
    echo json_encode([
        'content' => 'deleted',
        'text' => $lang['bookmark_add'],
        'tid' => $tid,
        'remove' => $remove,
    ]);
    die();
} else {
    $values = [
        'userid' => $current_user,
        'torrentid' => $tid,
    ];
    $fluent->insertInto('bookmarks')
        ->values($values)
        ->execute();
    $cache->delete('bookmarks_' . $current_user);
    echo json_encode([
        'content' => 'added',
        'text' => $lang['bookmarks_del'],
        'tid' => $tid,
        'remove' => $remove,
    ]);
    die();
}
