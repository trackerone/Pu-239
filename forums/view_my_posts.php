<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

$colour = $post_status_image = '';
$ASC_DESC = ((isset($_GET['ASC_DESC']) && $_GET['ASC_DESC'] === 'ASC') ? 'ASC ' : 'DESC ');
global $container;
$db = $container->get(Database::class);, $site_config, $CURUSER;

$fluent = $db; // alias
$fluent = $container->get(Database::class);
$count = $fluent->from('posts AS p')
                ->select(null)
                ->select('COUNT(p.id) AS count')
                ->leftJoin('topics AS t ON p.topic_id = t.id')
                ->leftJoin('forums AS f ON f.id = t.forum_id');
if ($CURUSER['class'] < UC_STAFF) {
    $count = $count->where('p.status = "ok"')
                   ->where('t.status = "ok"');
} elseif ($CURUSER['class'] < $site_config['forum_config']['min_delete_view_class']) {
    $count = $count->where('p.status != "deleted"')
                   ->where('t.status != "deleted"');
}
$count = $count->where('p.user_id = ?', $CURUSER['id'])
               ->where('f.min_class_read <= ?', $CURUSER['class'])
               ->fetch('count');

$page = isset($_GET['page']) ? (int) $_GET['page'] : 0;
$perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : 20;
$subscription_on_off = isset($_GET['s']) ? ($_GET['s'] == 1 ? '<br><div style="font-weight: bold;">' . _('Subscribed to topic') . ' <img src="' . $site_config['paths']['images_baseurl'] . 'forums/subscribe.gif" alt=" " class="emoticon"></div>' : '<br><div style="font-weight: bold;">' . _('Unsubscribed from topic') . ' <img src="' . $site_config['paths']['images_baseurl'] . 'forums/unsubscribe.gif" alt=" " class="emoticon"></div>') : '';
$link = $site_config['paths']['baseurl'] . '/forums.php?action=view_my_posts&amp;' . (isset($_GET['perpage']) ? "perpage={$perpage}&amp;" : '');
$pager = pager($perpage, $count, $link);
$menu_top = $pager['pagertop'];
$menu_bottom = $pager['pagerbottom'];
$LIMIT = $pager['limit'];
$rows = $db->fetchAll('SELECT p.id AS post_id, p.topic_id, p.user_id, p.added, p.body, p.edited_by, p.edit_date, p.icon, p.post_title, p.bbcode, p.post_history, p.edit_reason, p.status AS post_status, p.anonymous, t.id AS topic_id, t.topic_name, t.forum_id, t.sticky, t.locked, t.poll_id, t.status AS topic_status, f.name AS forum_name, f.description FROM posts AS p LEFT JOIN topics AS t ON p.topic_id=t.id LEFT JOIN forums AS f ON f.id=t.forum_id WHERE  ' . ($CURUSER['class'] < UC_STAFF ? 'p.status = \'ok\' AND t.status = \'ok\' AND' : ($CURUSER['class'] < $site_config['forum_config']['min_delete_view_class'] ? 'p.status != \'deleted\' AND t.status != \'deleted\'  AND' : '')) . ' p.user_id=' . $CURUSER['id'] . ' AND f.min_class_read <= ' . $CURUSER['class'] . ' ORDER BY p.id ' . $ASC_DESC . $LIMIT) or sqlerr(__FILE__, __LINE__);
$HTMLOUT .= $mini_menu . '<h1 class="has-text-centered">' . $count . ' ' . _('Posts by') . ' ' . format_username((int) $CURUSER['id']) . '</h1>
			<ul class="level-center bottom20">
                <li>
                    <a href="' . $site_config['paths']['baseurl'] . '/forums.php?action=view_my_posts" class="button is-small tooltipper" title="' . _('view posts from newest to oldest') . '">' . _('Sort by newest posts first') . '</a>
                </li>
                <li>
                    <a href="forums.php?action=view_my_posts&amp;ASC_DESC=ASC" class="button is-small tooltipper" title="' . _('view posts from oldest to newest') . '">' . _('Sort by oldest posts first') . '</a>
                </li>
            </ul>';
$HTMLOUT .= ($count > $perpage ? $menu_top : '') . '<a id="top"></a>';
foreach ($rows as $arr) {
    $topic_status = htmlsafechars($arr['topic_status']);
    switch ($topic_status) {
        case 'ok':
            $topic_status_image = '';
            break;

        case 'recycled':
            $topic_status_image = '<img src="' . $site_config['paths']['images_baseurl'] . 'forums/recycle_bin.gif" alt="' . _('Recycled') . '" title="' . _('This thread is currently') . ' ' . _('in the recycle-bin') . '" class="emoticon">';
            break;

        case 'deleted':
            $topic_status_image = '<img src="' . $site_config['paths']['images_baseurl'] . 'forums/delete_icon.gif" alt="' . _('Deleted') . '" title="' . _('This thread is currently') . ' ' . _('Deleted') . '" class="emoticon">';
            break;
    }
    $post_status = htmlsafechars($arr['post_status']);
    switch ($post_status) {
        case 'ok':
            $post_status = '';
            $post_status_image = '';
            break;

        case 'recycled':
            $post_status = 'recycled';
            $post_status_image = ' <img src="' . $site_config['paths']['images_baseurl'] . 'forums/recycle_bin.gif" alt="' . _('Recycled') . '" title="' . _('This post is currently') . ' ' . _('in the recycle-bin') . '" class="emoticon">';
            break;

        case 'deleted':
            $post_status = 'deleted';
            $post_status_image = ' <img src="' . $site_config['paths']['images_baseurl'] . 'forums/delete_icon.gif" alt="' . _('Deleted') . '" title="' . _('This post is currently') . ' ' . _('Deleted') . '" class="emoticon">';
            break;

        case 'postlocked':
            $post_status = 'postlocked';
            $post_status_image = ' <img src="' . $site_config['paths']['images_baseurl'] . 'forums/thread_locked.gif" alt="' . _('Locked') . '" title="' . _('This post is currently') . ' ' . _('Locked') . '" class="emoticon">';
            break;
    }
    $post_icon = (!empty($arr['icon']) ? '<img src="' . $site_config['paths']['images_baseurl'] . 'smilies/' . htmlsafechars($arr['icon']) . '.gif" alt="icon" title="icon" class="emoticon"> ' : '<img src="' . $site_config['paths']['images_baseurl'] . 'forums/topic_normal.gif" alt="Normal Topic" title="Normal Topic" class="emoticon"> ');
    $post_title = (!empty($arr['post_title']) ? ' <span style="font-weight: bold; font-size: x-small;">' . htmlsafechars($arr['post_title']) . '</span>' : '' . _('Link to Post') . '');
    $edited_by = '';
    if ($arr['edit_date'] > 0) {
        $res_edited = sql_query('SELECT username FROM users WHERE id=' . sqlesc($arr['edited_by'])) or sqlerr(__FILE__, __LINE__);
        $arr_edited = mysqli_fetch_assoc($res_edited);
        if ($arr['anonymous'] === '1') {
            if ($CURUSER['class'] < UC_STAFF && $arr['user_id'] != $CURUSER['id']) {
                $edited_by = '<br><br><br><span style="font-weight: bold; font-size: x-small;">' . _('Last edited by') . ' <i>' . get_anonymous_name() . '</i> at ' . get_date((int) $arr['edit_date'], '') . ' GMT ' . ($arr['edit_reason'] !== '' ? ' </span>[ ' . _('Reason') . ': ' . htmlsafechars($arr['edit_reason']) . ' ] <span style="font-weight: bold; font-size: x-small;">' : '') . '' . (($CURUSER['class'] >= UC_STAFF && $arr['post_history'] !== '') ? ' <a class="is-link" href="forums.php?action=view_post_history&amp;post_id=' . (int) $arr['post_id'] . '&amp;forum_id=' . (int) $arr['forum_id'] . '&amp;topic_id=' . (int) $arr['topic_id'] . '">' . _('read post history') . '</a></span><br>' : '');
            } else {
                $edited_by = '<br><br><br><span style="font-weight: bold; font-size: x-small;">' . _('Last edited by') . ' <i>' . get_anonymous_name() . '</i>[' . format_username((int) $arr['edited_by']) . '] at ' . get_date((int) $arr['edit_date'], '') . ' GMT ' . ($arr['edit_reason'] !== '' ? ' </span>[ ' . _('Reason') . ': ' . htmlsafechars($arr['edit_reason']) . ' ] <span style="font-weight: bold; font-size: x-small;">' : '') . '' . (($CURUSER['class'] >= UC_STAFF && $arr['post_history'] !== '') ? ' <a class="is-link" href="forums.php?action=view_post_history&amp;post_id=' . (int) $arr['post_id'] . '&amp;forum_id=' . (int) $arr['forum_id'] . '&amp;topic_id=' . (int) $arr['topic_id'] . '">' . _('read post history') . '</a></span><br>' : '');
            }
        } else {
            $edited_by = '<br><br><br><span style="font-weight: bold; font-size: x-small;">' . _('Last edited by') . ' ' . format_username((int) $arr['edited_by']) . ' at ' . get_date((int) $arr['edit_date'], '') . ' GMT ' . ($arr['edit_reason'] !== '' ? ' </span>[ ' . _('Reason') . ': ' . htmlsafechars($arr['edit_reason']) . ' ] <span style="font-weight: bold; font-size: x-small;">' : '') . '' . (($CURUSER['class'] >= UC_STAFF && $arr['post_history'] !== '') ? ' <a class="is-link" href="forums.php?action=view_post_history&amp;post_id=' . (int) $arr['post_id'] . '&amp;forum_id=' . (int) $arr['forum_id'] . '&amp;topic_id=' . (int) $arr['topic_id'] . '">' . _('read post history') . '</a></span><br>' : '');
        }
    }
    $body = $arr['bbcode'] === 'yes' ? format_comment($arr['body']) : format_comment_no_bbcode($arr['body']);
    $post_id = (int) $arr['post_id'];
    $HTMLOUT .= '
        <table class="table table-bordered table-striped">
        <tr>
            <td colspan="3">' . _('Forum') . ': <a class="is-link" href="forums.php?action=view_forum&amp;forum_id=' . (int) $arr['forum_id'] . '" title="' . _('Link to Forum') . '">
			    <span style="color: white;font-weight: bold;">' . htmlsafechars($arr['forum_name']) . '</span></a>&nbsp;&nbsp;&nbsp;&nbsp;
    			' . _('Topic') . ': <a class="is-link" href="forums.php?action=view_topic&amp;topic_id=' . (int) $arr['topic_id'] . '" title="' . _('Link to Forum') . '">
	    		<span style="color: white;font-weight: bold;">' . htmlsafechars($arr['topic_name']) . '</span></a>' . $topic_status_image . '
            </td>
        </tr>
		<tr>
            <td><a id="' . $post_id . '"></a></td>
			<td>
    			<span style="white-space:nowrap;">' . $post_icon . '
	    		<a class="is-link" href="forums.php?action=view_topic&amp;topic_id=' . (int) $arr['topic_id'] . '&amp;page=' . $page . '#' . $arr['post_id'] . '" title="' . _('Link to Post') . '">
		    	' . $post_title . '</a>&nbsp;&nbsp;' . $post_status_image . '
			    &nbsp;&nbsp; ' . _('Posted') . ': ' . get_date((int) $arr['added'], '') . ' [' . get_date((int) $arr['added'], '', 0, 1) . ']</span>
            </td>
			<td>
                <span style="white-space:nowrap;">
            		<a href="forums.php?action=view_my_posts&amp;page=' . $page . '#top"><img src="' . $site_config['paths']['images_baseurl'] . 'forums/up.gif" alt="' . _('Top') . '" class="emoticon"></a> 
                    <a href="forums.php?action=view_my_posts&amp;page=' . $page . '#bottom"><img src="' . $site_config['paths']['images_baseurl'] . 'forums/down.gif" alt="' . _('Bottom') . '" class="emoticon"></a>
    			</span>
            </td>
		</tr>
			<tr>
		    <td class="has-text-centered w-15 mw-150">' . get_avatar($CURUSER) . '<br>' . ($arr['anonymous'] === '1' ? '<i>' . get_anonymous_name() . '</i>' : format_username((int) $CURUSER['id'])) . ($arr['anonymous'] === '1' || empty($CURUSER['title']) ? '' : '<br><span style=" font-size: xx-small;">[' . htmlsafechars($CURUSER['title']) . ']</span>') . '<br><span style="font-weight: bold;">' . ($arr['anonymous'] === 'yes' ? '' : get_user_class_name((int) $CURUSER['class'])) . '</span><br></td>
    		<td colspan="2">' . $body . $edited_by . '</td>
		</tr>
    </table>';
}
$HTMLOUT .= '<a id="bottom"></a>' . ($count > $perpage ? $menu_bottom : '');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/forums.php'>" . _('Forums') . '</a>',
    "<a href='{$site_config['paths']['baseurl']}/forums.php?action=view_my_posts'>" . _('My Posts') . '</a>',
];
