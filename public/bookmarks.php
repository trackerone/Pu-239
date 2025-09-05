<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_torrenttable.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_categories.php';
$user = check_user_status();
global $container, $site_config;

$stdfoot = [
    'js' => [
        get_file_name('bookmarks_js'),
    ],
];

$HTMLOUT = '';

/**
 * @param        $res
 * @param        $userid
 * @param string $variant
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 *
 * @return string
 */
function bookmarktable($res, $userid, $variant = 'index')
{
    global $container, $site_config;

    $HTMLOUT = "
    <div class='has-text-centered bottom20'>
        " . _('Icon Legend :') . "
        <i class='icon-bookmark-empty icon has-text-danger'></i> = " . _('Delete Bookmark') . " | 
        <i class='icon-download icon'></i> = " . _('Download Torrent') . " | 
        <i class='icon-key icon has-text-success'></i> = " . _('Bookmark is Private') . " | 
        <i class='icon-users icon has-text-danger'></i> = " . _('Bookmark is Public') . '
    </div>';

    $heading = '
                    <tr>
                        <th>' . _('Type') . "</th>
                        <th class='has-text-left'>" . _('Name') . '</th>';
    $heading .= ($variant === 'index' ? '
                        <th>' . _('Delete') . '</th>
                        <th>' : '') . _('Download') . '</th>
                        <th>' . _('Share') . '</th>';
    if ($variant === 'mytorrents') {
        $heading .= '
                        <th>' . _('Edit') . '</th>
                        <th>' . _('Yes') . '</th>';
    }
    $heading .= '
                        <th>' . _('Files') . '</th>
                        <th>' . _('Comments') . '</th>
                        <th>' . _('Added') . '</th>
                        <th>' . _('Torrent Size') . '</th>
                        <th>' . _('Times Completed') . '</th>
                        <th>' . _('Seeders') . '</th>
                        <th>' . _('Leechers') . '</th>';
    if ($variant === 'index') {
        $heading .= '
                        <th>' . _('Upped by') . '</th>';
    }
    $heading .= '
                    </tr>';
    $body = '';
    $categories = genrelist(false);
    $change = [];
    foreach ($categories as $key => $value) {
        $change[$value['id']] = [
            'id' => $value['id'],
            'name' => $value['name'],
            'image' => $value['image'],
        ];
    }
    $fluent = $container->get(Database::class);
    foreach ($res as $row) {
        $row['cat_name'] = htmlsafechars($change[$row['category']]['name']);
        $row['cat_pic'] = htmlsafechars($change[$row['category']]['image']);
        $id = (int) $row['id'];
        $body .= "
                    <tr>
                        <td class='has-text-centered'>";
        if (isset($row['cat_name'])) {
            $body .= '<a href="' . $site_config['paths']['baseurl'] . '/browse.php?cat=' . (int) $row['category'] . '">';
            if (isset($row['cat_pic']) && $row['cat_pic'] != '') {
                $body .= "<img src='{$site_config['paths']['images_baseurl']}caticons/" . get_category_icons() . '/' . htmlsafechars($row['cat_pic']) . "' alt='" . htmlsafechars($row['cat_name']) . "' class='tooltipper' title='" . htmlsafechars($row['cat_name']) . "'>";
            } else {
                $body .= htmlsafechars($row['cat_name']);
            }
            $body .= '</a>';
        } else {
            $body .= '-';
        }
        $body .= '
                        </td>';
        $dispname = htmlsafechars($row['name']);
        $body .= "
                        <td class='has-text-left'>
                            <a href='{$site_config['paths']['baseurl']}/details.php?";
        if ($variant === 'mytorrents') {
            $body .= 'returnto=' . urlencode($_SERVER['REQUEST_URI']) . '&amp;';
        }
        $body .= "id=$id";
        if ($variant === 'index') {
            $body .= '&amp;hit=1';
        }
        $body .= "'><b>$dispname</b></a>&#160;
                        </td>";
        $body .= ($variant === 'index' ? "
                        <td class='has-text-centered'>
                            <span data-tid='{$id}' data-remove='true' data-private='false' class='bookmarks tooltipper' title='" . _('Delete Bookmark!') . "'>
                                <i class='icon-bookmark-empty icon has-text-danger'></i>
                            </span>
                        </td>" : '');
        $body .= ($variant === 'index' ? "
                        <td class='has-text-centered'>
                            <a href='{$site_config['paths']['baseurl']}/download.php?torrent={$id}' class='tooltipper' title='" . _('Download Bookmark!') . "'>
                                <i class='icon-download icon'></i>
                            </a>
                        </td>" : '');
        $bms = $fluent$sql = "SELECT * FROM 'bookmarks'"; $this->db->fetchAll($sql);;

    $HTMLOUT .= $count > $torrentsperpage ? $pager['pagertop'] : '';
    $HTMLOUT .= bookmarktable($bookmarks, $userid, 'index');
    $HTMLOUT .= $count > $torrentsperpage ? $pager['pagerbottom'] : '';
}
$title = _('Bookmarks');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
