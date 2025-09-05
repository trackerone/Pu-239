<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_pager.php';
$user = check_user_status();
global $container, $site_config;

$HTMLOUT = '';

$action = (isset($_GET['action']) ? htmlsafechars($_GET['action']) : (isset($_POST['action']) ? htmlsafechars($_POST['action']) : ''));
$mode = (isset($_GET['mode']) ? htmlsafechars($_GET['mode']) : '');
$fluent = $container->get(Database::class);
$subs = $container->get('subtitles');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'upload' || $action === 'edit') {
        $langs = isset($_POST['language']) ? htmlsafechars($_POST['language']) : '';
        if (empty($langs)) {
            stderr(_('Error'), _('No language selected'));
        }
        $releasename = isset($_POST['releasename']) ? htmlsafechars($_POST['releasename']) : '';
        if (empty($releasename)) {
            stderr(_('Error'), _('Use a descriptive name for your subtitle'));
        }
        $url = strip_tags(isset($_POST['imdb']) ? trim($_POST['imdb']) : '');
        $imdb = '';
        if (!empty($url)) {
            preg_match('/(tt\d{7,8})/i', $url, $imdb);
            $imdb = !empty($imdb[1]) ? 'https://www.imdb.com/title/' . $imdb[1] : '';
        }
        if (empty($imdb)) {
            stderr(_('Error'), _('Your IMDb link is invalid'));
        }
        $comment = isset($_POST['comment']) ? htmlsafechars($_POST['comment']) : '';
        $poster = isset($_POST['poster']) ? htmlsafechars($_POST['poster']) : '';
        $fps = isset($_POST['fps']) ? htmlsafechars($_POST['fps']) : '';
        $cd = isset($_POST['cd']) ? (int) $_POST['cd'] : '';
        if ($action === 'upload') {
            $file = $_FILES['sub'];
            if (!isset($file)) {
                stderr(_('Error'), _("The file can't be empty!"));
            }
            if ($file['size'] > $site_config['subtitles']['max_size']) {
                stderr(_('Error'), _('Your file is too big.'));
            }
            $fname = $file['name'];
            $temp_name = $file['tmp_name'];
            $ext = pathinfo($fname, PATHINFO_EXTENSION);
            $allowed = [
                'srt',
                'sub',
                'txt',
                'vtt',
            ];
            if (!in_array($ext, $allowed)) {
                stderr(_('Error'), _('File not allowed only .srt , .sub , .vtt or .txt files'));
            }
            $new_name = md5((string) TIME_NOW);
            $filename = "$new_name.$ext";
            $date = TIME_NOW;
            $owner = $user['id'];
            $values = [
                'name' => $releasename,
                'filename' => $filename,
                'imdb' => $imdb,
                'comment' => $comment,
                'lang' => $langs,
                'fps' => $fps,
                'poster' => $poster,
                'cds' => $cd,
                'added' => $date,
                'owner' => $owner,
            ];
            $id = // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;
            move_uploaded_file($temp_name, UPLOADSUB_DIR . $filename);
            header("Refresh: 0; url=subtitles.php?mode=details&id=$id");
        }
        if ($action === 'edit') {
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id == 0) {
                stderr(_('Error'), _('Invalid ID'));
            } else {
                $arr = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;
    $HTMLOUT .= "
    <ul class='bg-06 level-center'>
        <li class='margin10'><a href='subtitles.php?mode=upload'>" . _('Upload a Subtitle') . "</a></li>
    </ul>
    <div class='has-text-centered'>
        <h1>$title</h1>";
    $body = "
        <form action='subtitles.php' method='get' enctype='multipart/form-data' accept-charset='utf-8'>
            <div class='has-text-centered'>
                <input class='w-50 top20' value='" . $s . "' name='s' type='text'>
                <select name='w'>
                    <option value='name' " . ($w === 'name' ? 'selected' : '') . '>' . _('Name') . "</option>
                    <option value='imdb' " . ($w === 'imdb' ? 'selected' : '') . '>' . _('IMDb') . "</option>
                    <option value='comment' " . ($w === 'comment' ? 'selected' : '') . '>' . _('Comments') . "</option>
                </select>
            </div>
            <div class='has-text-centered'>
                <input type='submit' value='" . _('Search') . "' class='button is-small margin20'>
            </div>
        </form>";

    if ($count === 0) {
        $body .= "
        <div class='has-text-centered padding20'>
            " . _('Nothing found! Try again with a refined search string.') . '
        </div>';
    }
    $HTMLOUT .= '
    </div>' . main_div($body);
    if ($count > 0) {
        $HTMLOUT .= "
    <div class='top20'></div>";
        if ($count > $perpage) {
            $HTMLOUT .= $pager['pagertop'];
        }
        $heading = '
    <tr>
        <th>' . _('Language') . '</th>
        <th>' . _('Name') . '</th>
        <th>' . _('IMDb') . '</th>
        <th>' . _('Added') . '</th>
        <th>' . _('Hits') . '</th>
        <th>' . _('FPS') . '</th>
        <th>' . _('CD#') . '</th>
        <th>' . _('Tools') . '</th>
        <th>' . _('Uploader') . '</th>
    </tr>';

        $body = '';
        foreach ($select as $arr) {
            $langs = '<b>' . _('Unknown') . '</b>';
            foreach ($subs as $sub) {
                if ($sub['id'] == $arr['lang']) {
                    $langs = "<img src='{$site_config['paths']['images_baseurl']}/{$sub['pic']}' alt='{$sub['name']}' class='tooltipper left10' title='{$sub['name']}'>";
                    break;
                }
            }
            $body .= "
    <tr>
        <td class='has-text-centered'>{$langs}</td>
        <td><a href='{$site_config['paths']['baseurl']}/subtitles.php?mode=details&amp;id=" . $arr['id'] . "'>" . format_comment($arr['name']) . "</a></td>
        <td class='has-text-centered'>
            <a href='" . htmlsafechars($arr['imdb']) . "'  target='_blank'>
                <img src='{$site_config['paths']['images_baseurl']}imdb.svg' alt='Imdb' title='Imdb' class='tooltipper' width='50px'>
            </a>
        </td>
        <td class='has-text-centered'>" . get_date((int) $arr['added'], 'LONG', 0, 1) . "</td>
        <td class='has-text-centered'>" . $arr['hits'] . "</td>
        <td class='has-text-centered'>" . ($arr['fps'] === 0 ? '-' : format_comment($arr['fps'])) . "</td>
        <td class='has-text-centered'>" . ($arr['cds'] === 0 ? '-' : ($arr['cds'] == 255 ? _('More than 5') : $arr['cds'])) . '</td>';
            if ($arr['owner'] == $user['id'] || $user['class'] > UC_STAFF) {
                $body .= "
        <td class='has-text-centered'>
            <a href='subtitles.php?mode=edit&amp;id=" . $arr['id'] . "' title='" . _('Edit Subtitle') . "' class='tooltipper'>
                <i class='icon icon-edit' aria-hidden='true'></i>
            </a>
            <a href='subtitles.php?mode=delete&amp;id=" . $arr['id'] . "' title='" . _('Delete Subtitle') . "' class='tooltipper'>
                <i class='icon icon-trash-empty has-text-danger' aria-hidden='true'></i>
            </a>
        </td>";
            } else {
                $body .= '
        <td></td>';
            }
            $body .= "
        <td class='has-text-centered'>" . format_username((int) $arr['owner']) . '</td>
    </tr>';
        }
        $HTMLOUT .= main_table($body, $heading);
        if ($count > $perpage) {
            $HTMLOUT .= $pager['pagerbottom'];
        }
    }
    $title = _('Subtitles');
    $breadcrumbs = [
        "<a href='{$site_config['paths']['baseurl']}/browse.php'>" . _('Browse Torrents') . '</a>',
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
}
