<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

require_once __DIR__ . '/../include/bittorrent.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;

if (!defined('PU239_ROUTED')) {
    require_once __DIR__ . '/index.php';

    return;
}

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);

$allowedPlay = (int) $config->get('allowed.play');
$classNames = (array) $config->get('class_names');
$siteName = (string) $config->get('site.name');
$topScorePoints = (int) $config->get('arcade.top_score_points');
$gameNames = (array) $config->get('arcade.game_names');
$games = (array) $config->get('arcade.games');
$baseurl = (string) $config->get('paths.baseurl');
$imagesBase = (string) $config->get('paths.images_baseurl');

$user = check_user_status();

if ($user['class'] < $allowedPlay) {
    $requiredClassName = $classNames[$allowedPlay] ?? '';
    stderr(_('Error'), _fe('Sorry, you must be a {0} to play in the arcade!', $requiredClassName), 'bottom20');
} elseif ($user['game_access'] !== 1 || $user['status'] !== 0) {
    stderr(_('Error'), _('Your gaming rights have been disabled.'), 'bottom20', 'bottom20');
    app_halt('Exit called');
}

$HTMLOUT = "
            <div class='has-text-centered'>
                <h1>" . _fe('{0} Old School Arcade!', $siteName) . '</h1>
                <span>' . _fe('Top Scores Earn {0} Karma Points', $topScorePoints) . "</span>
                <div class='level-center top10'>
                    <a class='is-link' href='{$baseurl}/arcade_top_scores.php'>" . _('Top Scores') . '</a>
                </div>
            </div>';

$body = "
            <div class='level-center'>";

$list = $gameNames;
sort($list);
$i = 0;
foreach ($list as $gamename) {
    $id = $i++;
    $game_id = array_search($gamename, $gameNames, true);
    if ($game_id === false) {
        continue;
    }
    $game = $games[$game_id] ?? '';
    $fullgamename = $gameNames[$game_id] ?? '';
    $body .= "
                <div class='margin10 w-20'>
                    <a href='{$baseurl}/flash.php?gameURI={$game}.swf&amp;gamename={$game}&amp;game_id={$id}' class='tooltipper' title='" . urlencode($fullgamename) . "'>
                        <img src='{$imagesBase}games/{$game}.png' alt='{$game}' class='round10'>
                    </a>
                </div>";
}
$body .= '
            </div>';
$HTMLOUT .= main_div($body, 'top20');

$title = _('Arcade');
$breadcrumbs = [
    "<a href='{$baseurl}/games.php'>" . _('Games') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
