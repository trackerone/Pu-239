<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Torrent;

require_once __DIR__ . '/../../include/bittorrent.php';

$user = check_user_status();

// TODO(2025): add CSRF verification
$tvmazeId = (int) ($_POST['tvmazeid'] ?? 0);
$torrentId = (int) ($_POST['tid'] ?? 0);
$name = isset($_POST['name']) ? htmlsafechars((string) $_POST['name']) : null;

if ($user === false || $tvmazeId <= 0 || $torrentId <= 0) {
    json_out(['fail' => 'invalid']);
}

preg_match('/S(\d+)E(\d+)/i', (string) $name, $match);
$episode = !empty($match[2]) ? (int) $match[2] : 0;
$season = !empty($match[1]) ? (int) $match[1] : 0;

$torrents = $container->get(Torrent::class);
$poster = $torrents->get_items(['poster'], $torrentId);

if (empty($poster)) {
    $poster = get_image_by_id('tv', (string) $tvmazeId, 'poster', $season);
}

$poster = $poster ?: '';
$tvmazeData = tvmaze($tvmazeId, $torrentId, $season, $episode, $poster);

if (!empty($tvmazeData)) {
    json_out(['content' => $tvmazeData]);
}

json_out(['fail' => 'invalid']);
