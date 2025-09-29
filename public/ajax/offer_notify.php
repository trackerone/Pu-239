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

// TODO(2025): csrf on POST where missing
$offerId = (int) ($_POST['id'] ?? 0);
$notified = filter_var($_POST['notified'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

if ($offerId <= 0 || $notified === null) {
    json_out(['notify' => 'invalid']);
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

    json_out(['notify' => 0]);
}

$db->run(
    'INSERT INTO offer_notify (userid, offerid, added) VALUES (:userid, :offerid, :added)',
    $params + ['added' => [TIME_NOW, \PDO::PARAM_INT]]
);

$insertId = (int) $db->fetchValue('SELECT LAST_INSERT_ID()');

json_out(['notify' => $insertId]);
