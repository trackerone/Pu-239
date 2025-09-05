<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;
use Pu239\Session;
use Pu239\Torrent;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_pager.php';
$user = check_user_status();
global $container, $site_config;

$HTMLOUT = '';
if (empty($_GET['id'])) {
    $session = $container->get(Session::class);
    $session->set('is-warning', 'Invalid Information');
    header("Location: {$site_config['paths']['baseurl']}/index.php");
    app_halt('Exit called');
}
$id = (int) $_GET['id'];
if (!is_valid_id($id)) {
    stderr(_('Error'), _('Invalid ID'));
}

$fluent = $container->get(Database::class);
$count = $fluent$sql = "SELECT * FROM 'snatched AS s'"; $this->db->fetchAll($sql);;

$body = '';
foreach ($snatches as $arr) {
    $upspeed = ($arr['upspeed'] > 0 ? mksize($arr['upspeed']) : ($arr['seedtime'] > 0 ? mksize($arr['uploaded'] / ($arr['seedtime'] + $arr['leechtime'])) : mksize(0)));
    $downspeed = ($arr['downspeed'] > 0 ? mksize($arr['downspeed']) : ($arr['leechtime'] > 0 ? mksize($arr['downloaded'] / $arr['leechtime']) : mksize(0)));
    $ratio = ($arr['downloaded'] > 0 ? number_format($arr['uploaded'] / $arr['downloaded'], 3) : ($arr['uploaded'] > 0 ? 'Inf.' : '---'));
    $completed = sprintf('%.2f%%', 100 * (1 - ($arr['to_go'] / $arr['size'])));
    $snatchuser = (isset($arr['userid']) ? format_username((int) $arr['userid']) : _('Unknown'));
    $username = get_anonymous((int) $arr['owner']) || $arr['anonymous'] === '1' ? ($user['class'] < UC_STAFF && $arr['userid'] != $user['id'] ? '' : $snatchuser . ' - ') . '<i>' . _('Kezer Soze') . '</i>' : $snatchuser;
    $body .= "
        <tr>
            <td class='has-text-left'>{$username}</td>
            <td class='has-text-right'>" . mksize($arr['uploaded']) . "</td>
            <td class='has-text-right'>" . htmlsafechars($upspeed) . '/s</td>
            ' . ($site_config['site']['ratio_free'] ? '' : "<td class='has-text-right'>" . mksize($arr['downloaded']) . '</td>') . '
            ' . ($site_config['site']['ratio_free'] ? '' : "<td class='has-text-right'>" . htmlsafechars($downspeed) . '/s</td>') . "
            <td class='has-text-right'>" . htmlsafechars($ratio) . "</td>
            <td class='has-text-right'>" . htmlsafechars($completed) . "</td>
            <td class='has-text-right'>" . mkprettytime($arr['seedtime']) . "</td>
            <td class='has-text-right'>" . mkprettytime($arr['leechtime']) . "</td>
            <td class='has-text-centered'>" . get_date((int) $arr['last_action'], '', 0, 1) . "</td>
            <td class='has-text-centered'>" . get_date((int) $arr['complete_date'], '', 0, 1) . "</td>
            <td class='has-text-centered'>" . (int) $arr['timesann'] . '</td>
        </tr>';
}

$HTMLOUT .= main_table($body, $header);
if ($count > $perpage) {
    $HTMLOUT .= $pager['pagerbottom'];
}
$title = _('Snatches');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
