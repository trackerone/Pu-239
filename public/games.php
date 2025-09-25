<?php
declare(strict_types=1);

use Pu239\Config\ConfigRepository;
use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);
$allowedPlay = (int) $config->get('allowed.play');
$classNames = (array) $config->get('class_names');
$siteName = (string) $config->get('site.name');
$baseUrl = (string) $config->get('paths.baseurl');
$imagesBaseUrl = (string) $config->get('paths.images_baseurl');

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();

$HTMLOUT = '';
if ($user['class'] < $allowedPlay) {
    $requiredClassName = $classNames[$allowedPlay] ?? '';
    stderr(_('Error'), _fe('Sorry, you must be a {0} to play these games!', $requiredClassName), 'bottom20');
} elseif ($user['game_access'] !== 1 || $user['status'] !== 0) {
    stderr(_('Error'), _('Your gaming rights have been disabled.'), 'bottom20');
}

$width = 100 / 3;
$color1 = $color2 = $color3 = $color4 = $color5 = $color6 = $color7 = $color8 = $color9 = 'has-text-danger';

 $rows = $db->fetchAll('SELECT game_id FROM blackjack WHERE status = :status ORDER BY game_id', [
     ':status' => 'waiting',
 ]);
 foreach ($rows as $count) {
     $game_id = $count['game_id'];
     ${'color' . $game_id} = 'has-text-success';
 }

// Casino
$fluent = $db; // alias
// $fluent removed — use $this->db (ExtendedPdo)
$casino_count = $fluent->from('casino')
                       ->select(null)
                       ->select('COUNT(userid) AS count')
                       ->where('deposit > 0')
                       ->where('userid != ?', $user['id'])
                       ->fetch("count");
if ($casino_count > 0) {
    $color9 = 'green';
}

$HTMLOUT = "
            <div class='has-text-centered bottom20'>
                <h1>{$siteName} Games!</h1>
                <h3>" . _fe('Welcome To The {0} Casino, Please Select A Game Below To Play.', $siteName) . '</h3>
            </div>' . main_div("
            <div class='columns is-multiline is-variable is-0-mobile is-1-tablet is-2-desktop'>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=1'><div class='has-text-centered $color1'>" . _fe('Blackjack {0}', '1GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=10'><div class='has-text-centered $color2'>" . _fe('Blackjack {0}', '10GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=20'><div class='has-text-centered $color3'>" . _fe('Blackjack {0}', '20GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=50'><div class='has-text-centered $color4'>" . _fe('Blackjack {0}', '50GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/casino.php'><div class='has-text-centered $color9'>" . _('Casino') . "</div>
                        <img src='{$imagesBaseUrl}casino.jpg' alt='casino' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=100'><div class='has-text-centered $color5'>" . _fe('Blackjack {0}', '100GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=250'><div class='has-text-centered $color6'>" . _fe('Blackjack {0}', '250GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=500'><div class='has-text-centered $color7'>" . _fe('Blackjack {0}', '500GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=1000'><div class='has-text-centered $color8'>" . _fe('Blackjack {0}', '1TB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
            </div>", null, 'padding20');

$title = _('Games');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
