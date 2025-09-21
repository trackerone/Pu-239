<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;


global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$db     = $container->get(Database::class);
$fluent = $db;

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$agents = $fluent->from('peers')
    ->select(null)
    ->select('agent')
    ->select('LEFT(peer_id, 8) AS peer_id')
    ->groupBy('agent')
    ->groupBy('peer_id')
    ->fetchAll();


if (!empty($agents)) {
    $heading = '
        <tr>
            <th>' . _('Client') . '</th>
            <th>' . _('Peer ID') . '</th>
        </tr>';
    $body = '';
    foreach ($agents as $arr) {
        $body .= '
        <tr>
            <td>' . format_comment($arr['agent']) . '</td>
            <td>' . format_comment($arr['peer_id']) . '</td>
        </tr>';
    }
    $HTMLOUT = main_table($body, $heading);
} else {
    $HTMLOUT = stdmsg(_('Error'), _("There are no peers and therefore there are no client ID's"));
}
$title = _('Torrent Clients');
$breadcrumbs = [
    "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
