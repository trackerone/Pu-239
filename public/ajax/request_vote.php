<?php

declare(strict_types=1);

use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';

$user = check_user_status();

if ($user === false) {
    json_out(['voted' => 'invalid']);
}

// TODO(2025): csrf
$requestId = (int) ($_POST['id'] ?? 0);
$voted = $_POST['voted'] ?? null;

if ($requestId <= 0 || $voted === null) {
    json_out(['voted' => 'invalid']);
}

$params = [
    'user_id' => (int) $user['id'],
    'request_id' => $requestId,
];

if ($voted === 'yes') {
    $db->run(
        'UPDATE request_votes SET vote = :vote WHERE user_id = :user_id AND request_id = :request_id',
        $params + ['vote' => 'no']
    );

    json_out(['voted' => 'no']);
}

if ($voted === 'no') {
    $db->run(
        'DELETE FROM request_votes WHERE user_id = :user_id AND request_id = :request_id',
        $params
    );

    json_out(['voted' => 0]);
}

$db->run(
    'INSERT INTO request_votes (user_id, request_id, vote) VALUES (:user_id, :request_id, :vote)',
    $params + ['vote' => 'yes']
);

json_out(['voted' => 'yes']);
