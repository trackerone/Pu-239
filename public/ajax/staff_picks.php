<?php

declare(strict_types=1);

use Pu239\Cache;
use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$cache = $container->get(Cache::class);
$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';

$user = check_user_status();

header('Content-Type: application/json; charset=utf-8');

if ($user === false || $user['class'] < UC_STAFF) {
    echo json_encode(['pick' => 'class'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

// TODO(2025): csrf
$pick = (int) ($_POST['pick'] ?? -1);
$torrentId = (int) ($_POST['id'] ?? 0);

if ($pick < 0 || $torrentId <= 0) {
    echo json_encode(['pick' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
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
    $cache->delete('staff_picks_');

    echo json_encode(['pick' => $newValue], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

echo json_encode(['pick' => 'fail'], JSON_THROW_ON_ERROR);
app_halt('Exit called');
