<?php

declare(strict_types=1);

use Pu239\Cache;
use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$cache = $container->get(Cache::class);
$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';

$user = check_user_status();

header('Content-Type: application/json; charset=utf-8');

if ($user === false) {
    echo json_encode(['fail' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

// TODO(2025): csrf
$gameNumber = (int) ($_POST['gamenum'] ?? 0);
$questionId = (int) ($_POST['qid'] ?? 0);
$answer = (string) ($_POST['answer'] ?? '');
$userId = (int) $user['id'];

if ($gameNumber <= 0 || $questionId <= 0 || $answer === '') {
    echo json_encode(['fail' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

$correctAnswer = $db->fetch(
    'SELECT canswer FROM triviaq WHERE qid = :qid',
    ['qid' => [$questionId, \PDO::PARAM_INT]]
);

if ($correctAnswer === false) {
    echo json_encode(['fail' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

$existing = $db->fetch(
    'SELECT correct FROM triviausers WHERE user_id = :uid AND qid = :qid AND gamenum = :gamenum',
    [
        'uid' => [$userId, \PDO::PARAM_INT],
        'qid' => [$questionId, \PDO::PARAM_INT],
        'gamenum' => [$gameNumber, \PDO::PARAM_INT],
    ]
);

if ($existing !== false) {
    $answered = (int) ($existing['correct'] ?? 0) === 1
        ? "<h3 class='has-text-success top20'>" . _('Awesome, that was the correct answer') . '</h3>'
        : "<h3 class='has-text-danger top20'>" . _('Sorry, that was not the correct answer') . '</h3>';
} else {
    $values = [
        'user_id' => [$userId, \PDO::PARAM_INT],
        'gamenum' => [$gameNumber, \PDO::PARAM_INT],
        'qid' => [$questionId, \PDO::PARAM_INT],
        'date' => [date('Y-m-d H:i:s'), \PDO::PARAM_STR],
        'correct' => [$answer === ($correctAnswer['canswer'] ?? '') ? 1 : 0, \PDO::PARAM_INT],
    ];

    $answered = $values['correct'][0] === 1
        ? "<h3 class='has-text-success top20'>" . _('Awesome, that was the correct answer') . '</h3>'
        : "<h3 class='has-text-danger top20'>" . _('Sorry, that was not the correct answer') . '</h3>';

    $db->run(
        'INSERT INTO triviausers (user_id, gamenum, qid, date, correct) VALUES (:user_id, :gamenum, :qid, :date, :correct)',
        $values
    );
}

$cache->delete('triviaq_');
$table = trivia_table();
$cleanup = trivia_time();

echo json_encode([
    'content' => ($table['table'] ?? '') . $answered . trivia_clocks(),
    'round' => $cleanup['round'] ?? 0,
    'game' => $cleanup['game'] ?? 0,
], JSON_THROW_ON_ERROR);
app_halt('Exit called');
