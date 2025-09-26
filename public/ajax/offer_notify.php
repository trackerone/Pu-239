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

// TODO(2025): csrf on POST where missing
$offerId = (int) ($_POST['id'] ?? 0);
$notified = filter_var($_POST['notified'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

if ($offerId <= 0 || $notified === null) {
    echo json_encode(['notify' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

$params = [
    'userid' => (int) $user['id'],
    'offerid' => $offerId,
];

if ($notified) {
    $db->run(
        'DELETE FROM offer_notify WHERE userid = :userid AND offerid = :offerid',
        $params
    );

    echo json_encode(['notify' => 0], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

$db->run(
    'INSERT INTO offer_notify (userid, offerid, added) VALUES (:userid, :offerid, :added)',
    $params + ['added' => [TIME_NOW, \PDO::PARAM_INT]]
);

$insertId = (int) $db->fetchValue('SELECT LAST_INSERT_ID()');

echo json_encode(['notify' => $insertId], JSON_THROW_ON_ERROR);
app_halt('Exit called');
