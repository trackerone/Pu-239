<?php

declare(strict_types=1);

use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();

header('Content-Type: application/json; charset=utf-8');

if ($user === false) {
    echo json_encode(['notify' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

// TODO(2025): csrf
$requestId = (int) ($_POST['id'] ?? 0);
$notified = filter_var($_POST['notified'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

if ($requestId <= 0 || $notified === null) {
    echo json_encode(['notify' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

$params = [
    'userid' => (int) $user['id'],
    'requestid' => $requestId,
];

if ($notified) {
    $db->run(
        'DELETE FROM request_notify WHERE userid = :userid AND requestid = :requestid',
        $params
    );

    echo json_encode(['notify' => 0], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

$insertId = (int) $db->insert(
    'INSERT INTO request_notify (userid, requestid, added) VALUES (:userid, :requestid, :added)',
    $params + ['added' => [TIME_NOW, \PDO::PARAM_INT]]
);

echo json_encode(['notify' => $insertId], JSON_THROW_ON_ERROR);
app_halt('Exit called');
