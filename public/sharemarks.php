<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_torrenttable.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_categories.php';
$user = check_user_status();
$stdfoot = [
    'js' => [
        get_file_name('bookmarks_js'),
    ],
];

$HTMLOUT = '';

/**
 * @param        $res
 * @param        $userid
 * @param        $user
 * @param string $variant
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 *
 * @return string
 */
function sharetable($res, $userid, $user, $variant = 'index')
{
    global $container, $site_config;
    $HTMLOUT = "
        <div class='has-text-centered bottom20'>
            " . _('Icon Legend :') . "
            <i class='icon-bookmark-empty icon has-text-danger'></i>" . _(' = Delete Bookmark |') . "
            <i class='icon-download icon'></i>" . _(' = Download Torrent |') . "
            <i class='icon-bookmark-empty icon has-text-success'></i>" . _('Add Bookmark') . '
        </div>';

    $heading = '
        <tr>
            <th>Type</th>
            <th>Name</th>';
    //$userid=(int) $_GET['id'];
    if ($user['id'] === $userid) {
        $heading .= ($variant === 'index' ? '
            <th>Download</th>' : '') . '
            <th>Delete</th>';
    } else {
        $heading .= ($variant === 'index' ? '
            <th>Download</th>' : '') . '
            <th>Bookmark</th>';
    }
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
    $categories = genrelist(false);
    $change = [];
    foreach ($categories as $key => $value) {
        $change[$value['id']] = [
            'id' => $value['id'],
            'name' => $value['name'],
            'image' => $value['image'],
        ];
    }
    $body = '';
    foreach ($res as $row) {
        $row['cat_name'] = htmlsafechars($change[$row['category']]['name']);
        $row['cat_pic'] = htmlsafechars($change[$row['category']]['image']);
        $id = (int) $row['id'];
        $body .= '
        <tr>
            <td>';
        if (isset($row['cat_name'])) {
            $body .= "<a href='browse.php?cat=" . (int) $row['category'] . "'>";
            if (isset($row['cat_pic']) && $row['cat_pic'] != '') {
                $body .= "<img src='{$site_config['paths']['images_baseurl']}caticons/" . get_category_icons() . "/{$row['cat_pic']}' alt='{$row['cat_name']}'>";
            } else {
                $body .= $row['cat_name'];
            }
            $body .= '</a>';
        } else {
            $body .= '-';
        }
        $body .= '
            </td>';
        $dispname = htmlsafechars($row['name']);
        $body .= "
            <td><a href='details.php?";
        if ($variant === 'mytorrents') {
            $body .= 'returnto=' . urlencode($_SERVER['REQUEST_URI']) . '&amp;';
        }
        $body .= "id=$id";
        if ($variant === 'index') {
            $body .= '&amp;hit=1';
        }
        $body .= "'><b>$dispname</b></a>&#160;</td>";
        $body .= ($variant === 'index' ? "
                        <td>
                            <a href='{$site_config['paths']['baseurl']}/download.php?torrent={$id}' class='tooltipper' title='" . _('Download Bookmark!') . "'>
                                <i class='icon-download icon'></i>
                            </a>
                        </td>" : '');
        $fluent = $container->get(Database::class);
        $bms = $fluent$sql = "SELECT * FROM 'bookmarks'"; $this->db->fetchAll($sql);;

    $HTMLOUT .= $count > $torrentsperpage ? $pager['pagertop'] : '';
    $HTMLOUT .= sharetable($sharemarks, $userid, $user, 'index');
    $HTMLOUT .= $count > $torrentsperpage ? $pager['pagerbottom'] : '';
}

$title = _('Sharemarks');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
