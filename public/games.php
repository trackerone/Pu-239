<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
$user = check_user_status();
global $container;
$db = $container->get(Database::class);, $site_config;

$HTMLOUT = '';
if ($user['class'] < $site_config['allowed']['play']) {
    stderr(_('Error'), _fe('Sorry, you must be a {0} to play these games!', $site_config['class_names'][$site_config['allowed']['play']]), 'bottom20');
} elseif ($user['game_access'] !== 1 || $user['status'] !== 0) {
    stderr(_('Error'), _('Your gaming rights have been disabled.'), 'bottom20');
}

$width = 100 / 3;
$color1 = $color2 = $color3 = $color4 = $color5 = $color6 = $color7 = $color8 = $color9 = 'has-text-danger';

$sql = "SELECT game_id FROM blackjack WHERE status = 'waiting' ORDER BY game_id";
$res = sql_query($sql) or sqlerr(__FILE__, __LINE__);
while ($count = mysqli_fetch_array($res)) {
    $game_id = $count['game_id'];
    ${'color' . $game_id} = 'has-text-success';
}

// Casino
$fluent = $db; // alias
$fluent = $container->get(Database::class);
$casino_count = $fluent->from('casino')
                       ->select(null)
                       ->select('COUNT(userid) AS count')
                       ->where('deposit > 0')
                       ->where('userid != ?', $user['id'])
                       ->fetch('count');
if ($casino_count > 0) {
    $color9 = 'green';
}

$HTMLOUT = "
            <div class='has-text-centered bottom20'>
                <h1>{$site_config['site']['name']} Games!</h1>
                <h3>" . _fe('Welcome To The {0} Casino, Please Select A Game Below To Play.', $site_config['site']['name']) . '</h3>
            </div>' . main_div("
            <div class='columns is-multiline is-variable is-0-mobile is-1-tablet is-2-desktop'>
                <div class='column is-one-third'>
                    <a href='{$site_config['paths']['baseurl']}/blackjack.php?id=1'><div class='has-text-centered $color1'>" . _fe('Blackjack {0}', '1GB') . "</div>
                        <img src='{$site_config['paths']['images_baseurl']}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$site_config['paths']['baseurl']}/blackjack.php?id=10'><div class='has-text-centered $color2'>" . _fe('Blackjack {0}', '10GB') . "</div>
                        <img src='{$site_config['paths']['images_baseurl']}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$site_config['paths']['baseurl']}/blackjack.php?id=20'><div class='has-text-centered $color3'>" . _fe('Blackjack {0}', '20GB') . "</div>
                        <img src='{$site_config['paths']['images_baseurl']}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$site_config['paths']['baseurl']}/blackjack.php?id=50'><div class='has-text-centered $color4'>" . _fe('Blackjack {0}', '50GB') . "</div>
                        <img src='{$site_config['paths']['images_baseurl']}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$site_config['paths']['baseurl']}/casino.php'><div class='has-text-centered $color9'>" . _('Casino') . "</div>
                        <img src='{$site_config['paths']['images_baseurl']}casino.jpg' alt='casino' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$site_config['paths']['baseurl']}/blackjack.php?id=100'><div class='has-text-centered $color5'>" . _fe('Blackjack {0}', '100GB') . "</div>
                        <img src='{$site_config['paths']['images_baseurl']}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$site_config['paths']['baseurl']}/blackjack.php?id=250'><div class='has-text-centered $color6'>" . _fe('Blackjack {0}', '250GB') . "</div>
                        <img src='{$site_config['paths']['images_baseurl']}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$site_config['paths']['baseurl']}/blackjack.php?id=500'><div class='has-text-centered $color7'>" . _fe('Blackjack {0}', '500GB') . "</div>
                        <img src='{$site_config['paths']['images_baseurl']}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$site_config['paths']['baseurl']}/blackjack.php?id=1000'><div class='has-text-centered $color8'>" . _fe('Blackjack {0}', '1TB') . "</div>
                        <img src='{$site_config['paths']['images_baseurl']}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
            </div>", null, 'padding20');

$title = _('Games');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
