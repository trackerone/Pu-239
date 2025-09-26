<?php
<<<<<< codex/enforce-csrf-and-escape-output-hay3lv
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Database;

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';

$user = check_user_status();

header('Content-Type: application/json; charset=utf-8');
=======

declare(strict_types=1);

<<<<<< codex/enforce-csrf-and-escape-output-dxtuor
=======
use Pu239\Database;

>>>>>> master
require_once dirname(__DIR__) . '/bootstrap_web.php';
>>>>>> master

if ($user === false) {
    echo json_encode(['voted' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

<<<<<< codex/enforce-csrf-and-escape-output-hay3lv
// TODO(2025): csrf
$requestId = (int) ($_POST['id'] ?? 0);
$voted = $_POST['voted'] ?? null;

if ($requestId <= 0 || $voted === null) {
=======
$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';

$user = check_user_status();

header('Content-Type: application/json; charset=utf-8');

if ($user === false) {
>>>>>> master
    echo json_encode(['voted' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

<<<<<< codex/enforce-csrf-and-escape-output-hay3lv
$params = [
    'user_id' => (int) $user['id'],
    'request_id' => $requestId,
];

if ($voted === 'yes') {
    $db->run(
        'UPDATE request_votes SET vote = :vote WHERE user_id = :user_id AND request_id = :request_id',
        $params + ['vote' => 'no']
    );

    echo json_encode(['voted' => 'no'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

if ($voted === 'no') {
    $db->run(
        'DELETE FROM request_votes WHERE user_id = :user_id AND request_id = :request_id',
        $params
    );

    echo json_encode(['voted' => 0], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

=======
// TODO(2025): csrf
$requestId = (int) ($_POST['id'] ?? 0);
$voted = $_POST['voted'] ?? null;

if ($requestId <= 0 || $voted === null) {
    echo json_encode(['voted' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
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

    echo json_encode(['voted' => 'no'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

if ($voted === 'no') {
    $db->run(
        'DELETE FROM request_votes WHERE user_id = :user_id AND request_id = :request_id',
        $params
    );

    echo json_encode(['voted' => 0], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

>>>>>> master
$db->run(
    'INSERT INTO request_votes (user_id, request_id, vote) VALUES (:user_id, :request_id, :vote)',
    $params + ['vote' => 'yes']
);

echo json_encode(['voted' => 'yes'], JSON_THROW_ON_ERROR);
app_halt('Exit called');
