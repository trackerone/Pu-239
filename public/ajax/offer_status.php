<?php

declare(strict_types=1);

use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();

header('content-type: application/json');

if ($user === false || !has_access($user['class'], UC_STAFF, '')) {
    echo json_encode(['status' => 'invalid']);
    app_halt('Exit called');
}

// TODO(2025): csrf on POST where missing
$offerId = (int) ($_POST['id'] ?? 0);
$currentStatus = $_POST['status'] ?? '';

if ($offerId <= 0 || $currentStatus === '') {
    echo json_encode(['status' => 'invalid']);
    app_halt('Exit called');
}

$nextStatus = match ($currentStatus) {
    'pending' => 'approved',
    'approved' => 'denied',
    default => 'pending',
};

$db->run(
    'UPDATE offers SET status = :status WHERE id = :id',
    [
        'status' => $nextStatus,
        'id' => $offerId,
    ]
);

echo json_encode(['status' => $nextStatus]);
app_halt('Exit called');
