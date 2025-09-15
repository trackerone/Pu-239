<?php
declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

use Pu239\Database;
use Pu239\Roles;

global $container, $site_config;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_pager.php';
require_once CLASS_DIR . 'class_check.php';

$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$uporder = $_GET['uporder'] ?? '';
$catorder = $_GET['catorder'] ?? '';


$baseurl = $site_config['paths']['baseurl'];
$torrent_count_row = $db->fetch('SELECT COUNT(id) AS c FROM torrents');
$n_tor = isset($torrent_count_row['c']) ? (int) $torrent_count_row['c'] : 0;
$peer_count_row = $db->fetch('SELECT COUNT(id) AS c FROM peers');
$n_peers = isset($peer_count_row['c']) ? (int) $peer_count_row['c'] : 0;

$HTMLOUT = '';
$perpage = 25;

$uploader_order_map = [
    'lastul' => 'last DESC, name',
    'torrents' => 'n_t DESC, name',
    'peers' => 'n_p DESC, name',
    'uploader' => 'name',
];
$uploader_order = $uploader_order_map[$uporder] ?? 'name';

$uploader_count_row = $db->fetch(
    'SELECT COUNT(*) AS c
        FROM (
            SELECT u.id
            FROM users AS u
            WHERE (u.roles_mask & :role) != 0
            GROUP BY u.id
        ) AS uploaders',
    [
        ':role' => Roles::UPLOADER,
    ]
);
$uploader_count = isset($uploader_count_row['c']) ? (int) $uploader_count_row['c'] : 0;

$pager = pager($perpage, $uploader_count, "{$site_config['paths']['baseurl']}/staffpanel.php?tool=stats&amp;");

if ($uploader_count === 0) {
    $HTMLOUT .= stdmsg(_('Error'), _('No uploaders.'));
} else {
    if ($uploader_count > $perpage) {
        $HTMLOUT .= $pager['pagertop'];
    }

    $uploader_sql = "SELECT
            u.id,
            u.username AS name,
            MAX(t.added) AS last,
            COUNT(DISTINCT t.id) AS n_t,
            COUNT(p.id) AS n_p
        FROM users AS u
        LEFT JOIN torrents AS t ON u.id = t.owner
        LEFT JOIN peers AS p ON t.id = p.torrent
        WHERE (u.roles_mask & :role) != 0
        GROUP BY u.id, u.username
        ORDER BY {$uploader_order}
        LIMIT :limit OFFSET :offset";

    $uploader_stmt = $db->run($uploader_sql, [
        ':role' => Roles::UPLOADER,
        ':limit' => (int) $pager['pdo']['limit'],
        ':offset' => (int) $pager['pdo']['offset'],
    ]);
    $uploaders = $uploader_stmt->fetchAll();

    $catorder_link = htmlsafechars($catorder);
    $heading = "
    <tr>
        <th><a href='{$baseurl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=uploader&amp;catorder={$catorder_link}' class='colheadlink'>" . _('Uploader') . "</a></th>
        <th><a href='{$baseurl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=lastul&amp;catorder={$catorder_link}' class='colheadlink'>" . _('Last upload') . "</a></th>
        <th><a href='{$baseurl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=torrents&amp;catorder={$catorder_link}' class='colheadlink'>" . _('Torrents') . "</a></th>
        <th>Perc.</th>
        <th><a href='{$baseurl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=peers&amp;catorder={$catorder_link}' class='colheadlink'>" . _('Peers') . "</a></th>
        <th>Perc.</th>
    </tr>";

    $body = '';
    foreach ($uploaders as $uploader) {
        $user_id = (int) $uploader['id'];
        $torrent_total = (int) $uploader['n_t'];
        $peer_total = (int) $uploader['n_p'];
        $last_added = isset($uploader['last']) ? (int) $uploader['last'] : 0;
        $last_cell = $last_added > 0
            ? '>' . get_date($last_added, '') . ' (' . get_date($last_added, '', 0, 1) . ')'
            : "align='center'>---";
        $torrent_percent = $n_tor > 0 ? number_format(100 * $torrent_total / $n_tor, 1) . '%' : '---';
        $peer_percent = $n_peers > 0 ? number_format(100 * $peer_total / $n_peers, 1) . '%' : '---';

        $body .= '
    <tr>
        <td>' . format_username($user_id) . '</td>
        <td ' . $last_cell . '</td>
        <td>' . $torrent_total . '</td>
        <td>' . $torrent_percent . '</td>
        <td>' . $peer_total . '</td>
        <td>' . $peer_percent . '</td>
    </tr>';
    }

    $HTMLOUT .= main_table($body, $heading);

    if ($uploader_count > $perpage) {
        $HTMLOUT .= $pager['pagerbottom'];
    }
}

if ($n_tor === 0) {
    $HTMLOUT .= stdmsg(_('Error'), _('No categories defined!'));
} else {
    $category_order_map = [
        'lastul' => 'last DESC, c.name',
        'torrents' => 'n_t DESC, c.name',
        'peers' => 'n_p DESC, c.name',
        'category' => 'c.name',
    ];
    $category_order = $category_order_map[$catorder] ?? 'c.name';

    $category_sql = "SELECT
            c.name,
            MAX(t.added) AS last,
            COUNT(DISTINCT t.id) AS n_t,
            COUNT(p.id) AS n_p
        FROM categories AS c
        LEFT JOIN torrents AS t ON t.category = c.id
        LEFT JOIN peers AS p ON t.id = p.torrent
        GROUP BY c.id, c.name
        ORDER BY {$category_order}";

    $categories = $db->fetchAll($category_sql);

    if (empty($categories)) {
        $HTMLOUT .= stdmsg(_('Error'), _('No categories defined!'));
    } else {
        $uporder_link = htmlsafechars($uporder);
        $heading = "
        <tr>
            <th><a href='{$baseurl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder={$uporder_link}&amp;catorder=category' class='colheadlink'>" . _('Category') . "</a></th>
            <th><a href='{$baseurl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder={$uporder_link}&amp;catorder=lastul' class='colheadlink'>" . _('Last upload') . "</a></th>
            <th><a href='{$baseurl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder={$uporder_link}&amp;catorder=torrents' class='colheadlink'>" . _('Torrents') . "</a></th>
            <th>Perc.</th>
            <th><a href='{$baseurl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder={$uporder_link}&amp;catorder=peers' class='colheadlink'>" . _('Peers') . "</a></th>
            <th>Perc.</th>
        </tr>";

        $body = '';
        foreach ($categories as $category) {
            $category_name = htmlsafechars($category['name']);
            $category_torrents = (int) $category['n_t'];
            $category_peers = (int) $category['n_p'];
            $last_added = isset($category['last']) ? (int) $category['last'] : 0;
            $last_cell = $last_added > 0
                ? '>' . get_date($last_added, '') . ' (' . get_date($last_added, '', 0, 1) . ')'
                : "align='center'>---";
            $torrent_percent = $n_tor > 0 ? number_format(100 * $category_torrents / $n_tor, 1) . '%' : '---';
            $peer_percent = $n_peers > 0 ? number_format(100 * $category_peers / $n_peers, 1) . '%' : '---';

            $body .= '
        <tr>
            <td>' . $category_name . '</td>
            <td ' . $last_cell . '</td>
            <td>' . $category_torrents . '</td>
            <td>' . $torrent_percent . '</td>
            <td>' . $category_peers . '</td>
            <td>' . $peer_percent . '</td>
        </tr>';
        }

        $HTMLOUT .= main_table($body, $heading, null, 'top20');
    }
}

$title = _('Stats');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
