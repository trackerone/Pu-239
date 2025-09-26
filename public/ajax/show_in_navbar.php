<?php
<<<<<< codex/enforce-csrf-and-escape-output-dxtuor
declare(strict_types=1);
<<<<<< codex/enforce-csrf-and-escape-output-hay3lv

require_once dirname(__DIR__) . '/bootstrap_web.php';
=======

require_once dirname(__DIR__) . '/bootstrap_web.php';
=======

declare(strict_types=1);
>>>>>> master
>>>>>> master

use Pu239\Cache;
use Pu239\Database;

<<<<<< codex/enforce-csrf-and-escape-output-hay3lv
=======
<<<<<< codex/enforce-csrf-and-escape-output-dxtuor
=======
require_once dirname(__DIR__) . '/bootstrap_web.php';

>>>>>> master
>>>>>> master
$cache = $container->get(Cache::class);
$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';

$user = check_user_status();

header('Content-Type: application/json; charset=utf-8');

if ($user === false || $user['class'] < UC_STAFF) {
    echo json_encode(['show_in_navbar' => 'class'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

// TODO(2025): csrf
$panelId = (int) ($_POST['id'] ?? 0);
$currentValue = $_POST['show'] ?? null;

if ($panelId <= 0 || $currentValue === null) {
    echo json_encode(['show_in_navbar' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
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

    echo json_encode(['show_in_navbar' => $nextValue], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

echo json_encode(['show_in_navbar' => 'fail'], JSON_THROW_ON_ERROR);
app_halt('Exit called');
