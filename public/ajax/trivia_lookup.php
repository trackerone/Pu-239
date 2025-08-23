<?php
require_once __DIR__ . '/../../include/runtime_safe.php';

require_once __DIR__ . '/../../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Database;

require_once __DIR__ . '/../../include/bittorrent.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_trivia.php';
$curuser = check_user_status();
global $container;

header('content-type: application/json');
if (empty($curuser)) {
    echo json_encode(['fail' => 'csrf']);
    die();
}

$table = trivia_table();
$qid = $table['qid'];
$gamenum = $table['gamenum'];
$table = $table['table'];
$cache = $container->get(Cache::class);
$data = $cache->get('trivia_current_question_');
if (empty($data)) {
    echo json_encode(['fail' => 'invalid']);
    die();
}
$fluent = $container->get(Database::class);
$user = $fluent->from('triviausers')
               ->where('user_id = ?', $curuser['id'])
               ->where('qid = ?', $qid)
               ->where('gamenum = ?', $gamenum)
               ->fetch();

$cleanup = trivia_time();
if (!empty($user)) {
    if ($user['correct'] == 1) {
        $answered = "<h3 class='has-text-success top20'>" . _('Awesome, that was the correct answer') . '</h3>';
    } else {
        $answered = "<h3 class='has-text-danger top20'>" . _('Sorry, that was not the correct answer') . '</h3>';
    }
    echo json_encode([
        'content' => $table . $answered . trivia_clocks(),
        'round' => $cleanup['round'],
        'game' => $cleanup['game'],
    ]);
    die();
}

$question = $output = '';
$answers = [
    'answer1',
    'answer2',
    'answer3',
    'answer4',
    'answer5',
];
if (!empty($data['question'])) {
    $question = "
        <h2 class='bg-00 padding10 bottom10 round5'>" . format_comment($data['question']) . '</h2>';
}
foreach ($answers as $answer) {
    if (!empty($data[$answer])) {
        $output .= "
        <span id='{$answer}' class='size_4 margin10 trivia-pointer bg-00 round5 padding10' data-answer='{$answer}'  data-qid='{$qid}' data-gamenum='{$gamenum}' onclick=\"process_trivia('$answer')\">" . format_comment($data[$answer]) . '</span>';
    }
}
if (!empty($output)) {
    $output = "<div class='level-center'>$output</div>";
    echo json_encode([
        'content' => $question . $output . trivia_clocks(),
        'round' => $cleanup['round'],
        'game' => $cleanup['game'],
    ]);
    die();
}

echo json_encode(['fail' => 'invalid']);
die();
