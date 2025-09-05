<?php
require_once __DIR__ . '/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;
use Pu239\Session;
use Pu239\User;

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
$fluent = $container->get(Database::class);
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
// TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

$game_id = array_search($gname, $site_config['arcade']['games']);
$game = $site_config['arcade']['game_names'][$game_id];
$link = '[url=' . $site_config['paths']['baseurl'] . '/flash.php?gameURI=' . $gname . '.swf&gamename=' . $gname . '&game_id=' . $game_id . ']' . $game . '[/url]';
$classColor = get_user_class_color($user['class']);
$scores = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
} elseif (empty($high)) {
    $set = [
        'game' => $gname,
        'score' => $score,
        'level' => $level,
        'user_id' => $user['id'],
    ];
    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
}
header('Location: ' . $site_config['paths']['baseurl'] . "/arcade_top_scores.php#{$gname}");
