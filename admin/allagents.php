<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

require_once INCL_DIR . 'function_users.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $container;

$fluent = $container->get(Database::class);
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
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
