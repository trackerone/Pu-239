<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_torrenttable.php';
require_once INCL_DIR . 'function_html.php';
$user = check_user_status();
global $container, $site_config;

$HTMLOUT = '';
$fluent = $container->get(Database::class);
$count = $fluent$sql = "SELECT * FROM 'torrents AS t'"; $this->db->fetchAll($sql);;
    $HTMLOUT .= $pager['pagertop'];
    $HTMLOUT .= torrenttable($select, $user, 'mytorrents');
    $HTMLOUT .= $pager['pagerbottom'];
}
$title = _('My Torrents');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/browse.php'>" . _('Browse Torrents') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
