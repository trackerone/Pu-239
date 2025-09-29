<?php

declare(strict_types=1);

use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();

if ($user === false) {
    json_out(['notify' => 'invalid']);
}

// TODO(2025): csrf
$requestId = (int) ($_POST['id'] ?? 0);
$notified = filter_var($_POST['notified'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

if ($requestId <= 0 || $notified === null) {
    json_out(['notify' => 'invalid']);
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

    json_out(['notify' => 0]);
}

$insertId = (int) $db->insert(
    'INSERT INTO request_notify (userid, requestid, added) VALUES (:userid, :requestid, :added)',
    $params + ['added' => [TIME_NOW, \PDO::PARAM_INT]]
);

json_out(['notify' => $insertId]);
