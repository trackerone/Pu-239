<?php

declare(strict_types=1);

use Pu239\Database;
use Pu239\Torrent;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
check_user_status();

$tid = (int) ($_POST['tid'] ?? 0);
// TODO(2025): csrf
if (!empty($tid)) {
    $torrents_class = $container->get(Torrent::class);
    $descr = $torrents_class->format_descr($tid);
    if (!empty($descr)) {
        json_out(['descr' => $descr]);
    }
}
json_out([
    'fail' => 'invalid',
]);
