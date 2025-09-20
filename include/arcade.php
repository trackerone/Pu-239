<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime_safe.php';

use Pu239\Database;
use Pu239\Session;
use Pu239\User;

$db = $container->get(Database::class);

$user = check_user_status();
global $container, $site_config;

if (isset($_POST['gname'])) {
    $gname = htmlsafechars($_POST['gname']);
    $all_our_games = $site_config['arcade']['games'];
    if (!in_array($gname, $all_our_games)) {
        return false;
    }
} elseif (isset($_POST['func']) && $_POST['func'] === 'storeScore') {
    $gname = 'ms-pac-man';
}
if (isset($_POST['levelName'])) {
    $levelName = htmlsafechars($_POST['levelName']);
    $all_levels = [
        'LEVEL: SLUG',
        'LEVEL: WORM',
        'LEVEL: PYTHON',
    ];
    if (!in_array($levelName, $all_levels)) {
        return false;
    }
}
// using $db (ExtendedPdo)
$score = isset($_POST['score']) ? (int) $_POST['score'] : (isset($_POST['gscore']) ? (int) $_POST['gscore'] : 0);
$level = isset($_POST['level']) ? (int) $_POST['level'] : 1;
$values = [
    'game' => $gname,
    'user_id' => $user['id'],
    'level' => $level,
    'score' => $score,
];
if ($score === 0) {
    $session = $container->get(Session::class);
    $session->set('is-info', "Wow!! That was pretty bad. Don't worry, we will tell anyone about it.");
    header('Location: ' . $site_config['paths']['baseurl'] . "/arcade_top_scores.php#{$gname}");
    app_halt('Exit called');
}
$sql = "INSERT INTO flashscores (/* columns */) VALUES (/* values */)";
$db->perform($sql, $values);

$game_id = array_search($gname, $site_config['arcade']['games']);
$game = $site_config['arcade']['game_names'][$game_id];
$link = '[url=' . $site_config['paths']['baseurl'] . '/flash.php?gameURI=' . $gname . '.swf&gamename=' . $gname . '&game_id=' . $game_id . ']' . $game . '[/url]';
$classColor = get_user_class_color($user['class']);
$scores = $db->fetch(
    'SELECT score FROM flashscores WHERE game = :game AND score != :score ORDER BY level DESC, score DESC LIMIT 1',
    ['game' => $gname, 'score' => $score]
);
$highScore = $scores['score'] ?? 0;
if ($highScore < $score) {
    $message = "[color=#$classColor][b]{$user['username']}[/b][/color] has just set a new high score of " . number_format($score) . " in $link and earned {$site_config['arcade']['top_score_points']} karma points.";
    $bonuscomment = get_date((int) TIME_NOW, 'DATE', 1) . " - {$site_config['arcade']['top_score_points']} Points for setting a new high score in $game.\n ";
    $set = [
        'bonuscomment' => $bonuscomment . $user['bonuscomment'],
        'seedbonus' => $site_config['arcade']['top_score_points'] + $user['seedbonus'],
    ];
    $users_class = $container->get(User::class);
    $users_class->update($set, $user['id']);
} elseif ($score >= .9 * $highScore) {
    $message = "[color=#$classColor][b]" . format_comment($user['username']) . "[/b][/color] has just played $link and scored a whopping " . number_format($score) . '. Excellent! The high score remains ' . number_format($highScore) . '.';
} else {
    $message = "[color=#$classColor][b]" . format_comment($user['username']) . "[/b][/color] has just played $link and scored a measly " . number_format($score) . '. Try again. The high score remains ' . number_format($highScore) . '.';
}
if ($site_config['site']['autoshout_chat']) {
    require_once INCL_DIR . 'function_users.php';
    autoshout($message);
}
$high = $db->fetch(
    'SELECT score FROM highscores WHERE game = :game',
    ['game' => $gname]
);
$high = $high['score'] ?? null;
if (!empty($high) && $score > $high) {
    $update = [
        'score' => $score,
        'level' => $level,
        'user_id' => $user['id'],
    ];
    $sql = "UPDATE highscores SET /* columns */ WHERE game = :game";
$db->perform($sql, array_merge($update, ['game' => $gname]));
} elseif (empty($high)) {
    $set = [
        'game' => $gname,
        'score' => $score,
        'level' => $level,
        'user_id' => $user['id'],
    ];
    $sql = "INSERT INTO highscores (/* columns */) VALUES (/* values */)";
$db->perform($sql, $values);
}
header('Location: ' . $site_config['paths']['baseurl'] . "/arcade_top_scores.php#{$gname}");
