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
$baseUrl = (string) $config->get('paths.baseurl');
$imagesBaseUrl = (string) $config->get('paths.images_baseurl');

require_once __DIR__ . '/../include/bittorrent.php';
check_user_status();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!is_valid_id($id)) {
    stderr(_('Error'), _('Invalid ID'));
}

// $fluent removed — use $this->db (ExtendedPdo)
$count = $fluent->from('files')
                ->select(null)
                ->select('COUNT(id) AS count')
                ->where('torrent = ?', $id)
                ->fetch("count");
$perpage = 50;
$pager = pager($perpage, $count, $baseUrl . "/filelist.php?id={$id}&amp;");
$HTMLOUT = '';
if ($count > $perpage) {
    $HTMLOUT .= $pager['pagertop'];
}

$files = $fluent->from('files')
                ->where('torrent = ?', $id)
                ->orderBy('id')
                ->limit($pager['pdo']['limit'])
                ->offset($pager['pdo']['offset']);

$header = "
            <tr>
                <th class='has-text-centered w-1'>" . _('Type') . '</th>
                <th>' . _('Path') . "</th>
                <th class='has-text-right w-10'>" . _('Size') . '</th>
            </tr>';
$body = '';
foreach ($files as $subrow) {
    $ext = pathinfo($subrow['filename'], PATHINFO_EXTENSION);
    $ext = !empty($ext) ? $ext : 'Unknown';
    if (!file_exists(IMAGES_DIR . "icons/{$ext}.png")) {
        $ext = 'Unknown';
    }
    $body .= "
            <tr>
                <td class='has-text-centered'>
                    <img src='{$imagesBaseUrl}icons/" . htmlsafechars($ext) . ".png' class='tooltipper icon' alt='" . htmlsafechars($ext) . " file' title='" . _fe('{0} file', format_comment($ext)) . "'></td>
                <td>" . htmlsafechars($subrow['filename']) . "</td>
                <td class='has-text-right'>" . mksize($subrow['size']) . '</td>
            </tr>';
}

$HTMLOUT .= main_table($body, $header);

if ($count > $perpage) {
    $HTMLOUT .= $pager['pagerbottom'];
}
$title = _('Filelist');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
