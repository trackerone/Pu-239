<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

global $site_config, $CURUSER;

$limit = preg_match('/edit_post/', $_SERVER['QUERY_STRING']) ? '1, 10' : '10';
$res_posts = sql_query('SELECT p.id AS post_id, p.user_id, p.added, p.body, p.icon, p.post_title, p.bbcode, p.anonymous, u.id, u.username, u.class, u.donor, u.warned, u.status, u.avatar, u.chatpost, u.leechwarn, u.pirate, u.king, u.offensive_avatar FROM posts AS p LEFT JOIN users AS u ON p.user_id=u.id WHERE ' . ($CURUSER['class'] < UC_STAFF ? 'p.status = \'ok\' AND' : ($CURUSER['class'] < $site_config['forum_config']['min_delete_view_class'] ? 'p.status != \'deleted\' AND' : '')) . '  topic_id=' . sqlesc($topic_id) . ' ORDER BY p.id DESC LIMIT ' . $limit) or sqlerr(__FILE__, __LINE__);
$HTMLOUT .= '<h2 class="has-text-centered">' . _('last ten posts in reverse order') . '</h2>';

while ($arr = mysqli_fetch_assoc($res_posts)) {
    $HTMLOUT .= '
    <table class="table table-bordered table-striped">
        <tr>
            <td>
                <a id="' . (int) $arr['post_id'] . '"></a>
                <span style="white-space:nowrap;">#' . (int) $arr['post_id'] . '
                    <span style="font-weight: bold;">' . ($arr['anonymous'] === '1' ? '<i>' . get_anonymous_name() . '</i>' : format_comment($arr['username'])) . '</span>
                </span>
            </td>
            <td>
                <span style="white-space:nowrap;"> ' . _('Posted') . ': ' . get_date((int) $arr['added'], '') . ' [' . get_date((int) $arr['added'], '', 0, 1) . ']</span>
            </td>
        </tr>';
    if ($arr['anonymous'] === '1') {
        if ($CURUSER['class'] < UC_STAFF && $arr['user_id'] != $CURUSER['id']) {
            $HTMLOUT .= '
        <tr>
            <td class="has-text-centered w-15 mw-150">' . get_avatar($arr) . '<br><i>' . get_anonymous_name() . '</i></td>';
        } else {
            $HTMLOUT .= '
        <tr>
            <td class="has-text-centered w-15 mw-150">' . get_avatar($arr) . '<br><i>' . get_anonymous_name() . '</i>[' . format_username((int) $arr['user_id']) . ']</td>';
        }
    } else {
        $HTMLOUT .= '
        <tr>
            <td class="has-text-centered w-15 mw-150">' . get_avatar($arr) . '<br>' . format_username((int) $arr['user_id']) . '</td>';
    }
    $HTMLOUT .= '
            <td colspan="2">' . ($arr['bbcode'] == 'yes' ? format_comment($arr['body']) : format_comment_no_bbcode($arr['body'])) . '</td>
        </tr>
    </table>';
}
