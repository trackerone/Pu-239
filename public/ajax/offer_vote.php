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

// TODO(2025): csrf on POST where missing
$offerId = (int) ($_POST['id'] ?? 0);
$currentVote = $_POST['voted'] ?? '';

if ($offerId <= 0 || $currentVote === '') {
    json_out(['voted' => 'invalid']);
}

$params = [
    'offer_id' => $offerId,
    'user_id' => (int) $user['id'],
];

if ($currentVote === 'yes') {
    $db->run(
        'UPDATE offer_votes SET vote = :vote WHERE offer_id = :offer_id AND user_id = :user_id',
        $params + ['vote' => 'no']
    );

    json_out(['voted' => 'no']);
}

if ($currentVote === 'no') {
    $db->run(
        'DELETE FROM offer_votes WHERE offer_id = :offer_id AND user_id = :user_id',
        $params
    );

    json_out(['voted' => 0]);
}

$db->run(
    'INSERT INTO offer_votes (vote, user_id, offer_id, added) VALUES (:vote, :user_id, :offer_id, :added)',
    $params + [
        'vote' => 'yes',
        'added' => [TIME_NOW, \PDO::PARAM_INT],
    ]
);

json_out(['voted' => 'yes']);
