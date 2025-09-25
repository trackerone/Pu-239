<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;

require_once __DIR__ . '/../include/bittorrent.php';

check_user_status();

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);
/** @var Cache $cache */
$cache = $container->get(Cache::class);

$baseUrl = (string) $config->get('paths.baseurl');
$imagesBaseUrl = (string) $config->get('paths.images_baseurl');
$HTMLOUT = '';

$abba = '
        <h1 class="has-text-centered">Top Moods</h1>
        <div class="has-text-centered bottom20">You may select your mood by clicking on the smiley in the left side menu or clicking <a href="javascript:;" onclick="PopUp(\'usermood.php\',\'Mood\',530,500,1,1);"><span class="has-text-success">here</span></a>.</div>
         <table class="table table-bordered table-striped">
         <tr><td class="colhead">Count</td>
         <td class="colhead">Mood</td>
         <td class="colhead">Icon</td>
         </tr>';
$key = 'topmoods';
$topmoods = $cache->get($key);
if ($topmoods === false || is_null($topmoods)) {
    $rows = $db->fetchAll('SELECT moods.*, users.mood, COUNT(users.mood) as moodcount ' . 'FROM users LEFT JOIN moods ON (users.mood = moods.id) GROUP BY users.mood ' . 'ORDER BY moodcount DESC, moods.id');
    foreach ($rows as $arr) {
        $topmoods .= '<tr><td>' . (int) $arr['moodcount'] . '</td>
                 <td>' . htmlsafechars($arr['name']) . ' ' . ($arr['bonus'] == 1 ? '<a href="' . $baseUrl . '/mybonus.php">(bonus)</a>' : '') . '</td>
                 <td><img src="' . $imagesBaseUrl . 'smilies/' . htmlsafechars($arr['image']) . '" alt=""></td>
                 </tr>';
    }
    $cache->set($key, $topmoods, 0);
}
$HTMLOUT .= $abba . $topmoods . '</table>';
$title = _('Top Moods');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
