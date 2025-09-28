<?php

declare(strict_types=1);

use Pu239\Cache;
use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$cache = $container->get(Cache::class);
$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';

$user = check_user_status();

if ($user === false || $user['class'] < UC_STAFF) {
    json_out(['show_in_navbar' => 'class']);
}

// TODO(2025): csrf
$panelId = (int) ($_POST['id'] ?? 0);
$currentValue = $_POST['show'] ?? null;

if ($panelId <= 0 || $currentValue === null) {
    json_out(['show_in_navbar' => 'invalid']);
}

$nextValue = (int) $currentValue === 0 ? 1 : 0;

$statement = $db->run(
    'UPDATE staffpanel SET navbar = :navbar WHERE id = :id',
    [
        'navbar' => [$nextValue, \PDO::PARAM_INT],
        'id' => [$panelId, \PDO::PARAM_INT],
    ]
);

if ($statement->rowCount() > 0) {
    $cache->delete('staff_panels_' . (int) $user['class']);

    json_out(['show_in_navbar' => $nextValue]);
}

json_out(['show_in_navbar' => 'fail']);
