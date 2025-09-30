<?php

declare(strict_types=1);

use Pu239\Cache;
use Pu239\Database;
use PU239\Support\Audit;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$cache = $container->get(Cache::class);
$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';

$user = check_user_status();

if ($user === false || $user['class'] < UC_STAFF) {
    json_out(['pick' => 'class']);
}

// TODO(2025): csrf
$pick = (int) ($_POST['pick'] ?? -1);
$torrentId = (int) ($_POST['id'] ?? 0);

if ($pick < 0 || $torrentId <= 0) {
    json_out(['pick' => 'invalid']);
}

$newValue = $pick === 0 ? TIME_NOW : 0;

$statement = $db->run(
    'UPDATE torrents SET staff_picks = :staff_picks WHERE id = :id',
    [
        'staff_picks' => [$newValue, \PDO::PARAM_INT],
        'id' => [$torrentId, \PDO::PARAM_INT],
    ]
);

if ($statement->rowCount() > 0) {
    $operation = $pick === 0 ? 'staff_picks.enable' : 'staff_picks.disable';
    Audit::log($user['id'] ?? null, 'torrent.moderate', [
        'id' => $torrentId,
        'op' => $operation,
    ]);

    $cache->delete('staff_picks_');

    json_out(['pick' => $newValue]);
}

json_out(['pick' => 'fail']);
