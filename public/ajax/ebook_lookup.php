<?php

declare(strict_types=1);

use Pu239\Database;
use Pu239\Torrent;
use Rakit\Validation\Validator;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
check_user_status();

$validator = $container->get(Validator::class);
$validation = $validator->validate($_POST, [
    'isbn' => 'regex:/[0-9Xx]*/',
    'tid' => 'required|integer',
    'name' => 'required',
]);
if ($validation->fails()) {
    json_out(['content' => 'invalid']);
}
// TODO(2025): csrf on POST where missing
$tid = (int) ($_POST['tid'] ?? 0);
$torrents_class = $container->get(Torrent::class);
$torrent = $torrents_class->get($tid);
$poster = !empty($torrent['poster']) ? $torrent['poster'] : '';
$isbn = !empty($_POST['isbn']) ? $_POST['isbn'] : '000000';
$title = htmlsafechars($_POST['name']);
$book_info = get_book_info($isbn, $title, $tid, $poster);
if (!empty($book_info)) {
    json_out(['content' => $book_info['ebook']]);
}

json_out(['content' => 'Lookup Failed']);
