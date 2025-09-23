<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();
$parents = genrelist(true);
$baseurl = (string) $config->get('paths.baseurl');

$heading = "
        <tr>
            <th class='has-text-centered w-25'>" . _('Cat ID') . "</th>
            <th class='has-text-centered'>" . _('Cat Name') . "</th>
            <th class='has-text-centered w-25'>" . _('Torrents Uploaded') . '</th>
        </tr>';
$body = '';

// $fluent removed — use $this->db (ExtendedPdo)
$counts = $fluent->from('torrents')
                 ->select(null)
                 ->select('category')
                 ->select('COUNT(id) AS count')
                 ->groupBy('category')
                 ->fetchPairs('category', 'COUNT(id)');

$child = [
    'id' => '',
    'name' => '',
];
foreach ($parents as $parent) {
    if (!$user['hidden'] && $parent['hidden'] === 1) {
        continue;
    }
    foreach ($parent['children'] as $child) {
        if (!$user['hidden'] && $child['hidden'] === 1) {
            continue;
        }
        $count = !empty($counts) && !empty($counts[$child['id']]) ? $counts[$child['id']] : 0;
        $body .= "
        <tr>
            <td class='has-text-centered'>{$child['id']}</td>
            <td><a href='{$baseurl}/browse.php?cats[]={$child['id']}'>{$parent['name']}::{$child['name']}</a></td>
            <td class='has-text-centered'>$count</td>
        </tr>";
    }
}

$HTMLOUT = "
    <h1 class='has-text-centered'>" . _("Category ID's") . '</h1>';
$HTMLOUT .= main_table($body, $heading, 'w-50 has-text-centered');
$title = _("Category ID's");
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
