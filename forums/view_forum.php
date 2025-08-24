<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

require_once INCL_DIR . 'function_html.php';
$posts = $delete_me = $rpic = $content = $child = $parent_forum_name = $first_post_arr = $post_status_image = $sub_forums = $locked = '';
$forum_id = isset($_GET['forum_id']) ? (int) $_GET['forum_id'] : (isset($_POST['forum_id']) ? (int) $_POST['forum_id'] : 0);
global $container;
$db = $container->get(Database::class);, $site_config, $CURUSER;

if (!is_valid_id($forum_id)) {
    stderr(_('Error'), _('Bad ID.'));
}
$fluent = $db; // alias
$fluent = $container->get(Database::class);
$fluent->deleteFrom('now_viewing')
       ->where('user_id = ?', $CURUSER['id'])
       ->execute();
$values = [
    'user_id' => $CURUSER['id'],
    'forum_id' => $forum_id,
    'added' => TIME_NOW,
];
$fluent->insertInto('now_viewing')
       ->values($values)
       ->execute();

$arr = $fluent->from('forums')
              ->where('min_class_read <= ?', $CURUSER['class'])
              ->where('id = ?', $forum_id)
              ->limit(1)
              ->fetch();

$forum_name = !empty($arr['name']) ? format_comment($arr['name']) : '';

$parent_forum_id = $arr['parent_forum'];
if ($CURUSER['class'] < $arr['min_class_read']) {
    stderr(_('Error'), _('Bad ID.'));
}
$may_post = $CURUSER['class'] >= $arr['min_class_write'] && $CURUSER['class'] >= $arr['min_class_create'] && $CURUSER['forum_post'] === 'yes' && $CURUSER['status'] === 0;

$query = $fluent->from('forums')
                ->select(null)
                ->select('id AS sub_forum_id')
                ->select('name AS sub_form_name')
                ->select('description AS sub_form_description')
                ->select('min_class_read')
                ->select('post_count AS sub_form_post_count')
                ->select('topic_count AS sub_form_topic_count')
                ->where('min_class_read <= ?', $CURUSER['class'])
                ->where('parent_forum = ?', $forum_id)
                ->orderBy('sort')
                ->fetchAll();

$sub_forums_stuff = '';

foreach ($query as $sub_forums_arr) {
    if ($sub_forums_arr['min_class_read'] > $CURUSER['class']) {
        app_halt('Exit called');
    }

    $where = $CURUSER['class'] < UC_STAFF ? 'posts.status = "ok" AND topics.status = "ok"' : $CURUSER['class'] < $site_config['forum_config']['min_delete_view_class'] ? 'posts.status != "deleted"  AND topics.status != "deleted"' : '';
    $post_arr = $fluent->from('topics')
                       ->select(null)
                       ->select('topics.id AS topic_id')
                       ->select('topics.topic_name')
                       ->select('topics.status AS topic_status')
                       ->select('topics.anonymous AS tan')
                       ->select('posts.id AS last_post_id')
                       ->select('posts.topic_id')
                       ->select('posts.added')
                       ->select('posts.anonymous AS pan')
                       ->select('posts.id as post_id')
                       ->select('users.id AS user_id')
                       ->select('users.class')
                       ->innerJoin('posts ON topics.id=posts.topic_id')
                       ->leftJoin('users ON posts.user_id=users.id')
                       ->where($where)
                       ->where('topics.forum_id = ?', $sub_forums_arr['sub_forum_id'])
                       ->orderBy('posts.id DESC')
                       ->limit(1)
                       ->fetch();

    if ($post_arr['last_post_id'] > 0) {
        $last_topic_id = (int) $post_arr['topic_id'];
        $last_post_id = (int) $post_arr['last_post_id'];

        $topic_status = htmlsafechars($post_arr['topic_status']);
        switch ($topic_status) {
            case 'ok':
                $topic_status_image = '';
                break;

            case 'recycled':
                $topic_status_image = ' <img src="' . $site_config['paths']['images_baseurl'] . 'forums/recycle_bin.gif" alt="' . _('Recycled') . '" title="' . _('This topic is currently') . ' ' . _('in the recycle-bin') . '" class="tooltipper icon">';
                break;

            case 'deleted':
                $topic_status_image = ' <img src="' . $site_config['paths']['images_baseurl'] . 'forums/delete_icon.gif" alt="' . _('Deleted') . '" title="' . _('This topic is currently') . ' ' . _('Deleted') . '" class="tooltipper icon">';
                break;
        }
        if ($post_arr['tan'] === 1) {
            if ($CURUSER['class'] < UC_STAFF && $post_arr['user_id'] != $CURUSER['id']) {
                $last_post = '<span style="white-space:nowrap;">' . _('Last Post by') . ': <i>' . get_anonymous_name() . '</i> in &#9658; <a class="is-link" href="' . $site_config['paths']['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $last_topic_id . '&amp;page=last#' . $last_post_id . '" title="' . htmlsafechars($post_arr['topic_name']) . '">
						<span style="font-weight: bold;">' . CutName(htmlsafechars($post_arr['topic_name']), 30) . '</span></a>' . $topic_status_image . '<br>
						' . get_date((int) $post_arr['added'], '') . '<br></span>';
            } else {
                $last_post = '<span style="white-space:nowrap;">' . _('Last Post by') . ': <i>' . get_anonymous_name() . '</i> [' . format_username((int) $post_arr['user_id']) . ']
						<span style="font-size: x-small;"> [ ' . get_user_class_name((int) $post_arr['class']) . ' ] </span><br>
						in &#9658; <a class="is-link" href="' . $site_config['paths']['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $last_topic_id . '&amp;page=last#' . $last_post_id . '" title="' . htmlsafechars($post_arr['topic_name']) . '">
						<span style="font-weight: bold;">' . CutName(htmlsafechars($post_arr['topic_name']), 30) . '</span></a>' . $topic_status_image . '<br>
						' . get_date((int) $post_arr['added'], '') . '<br></span>';
            }
        } else {
            $last_post = '<span style="white-space:nowrap;">' . _('Last Post by') . ': ' . format_username((int) $post_arr['user_id']) . '
						<span style="font-size: x-small;"> [ ' . get_user_class_name((int) $post_arr['class']) . ' ] </span><br>
						in &#9658; <a class="is-link" href="' . $site_config['paths']['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $last_topic_id . '&amp;page=last#' . $last_post_id . '" title="' . htmlsafechars($post_arr['topic_name']) . '">
						<span style="font-weight: bold;">' . CutName(htmlsafechars($post_arr['topic_name']), 30) . '</span></a>' . $topic_status_image . '<br>
						' . get_date((int) $post_arr['added'], '') . '<br></span>';
        }
        $first_post_arr = [
            'first_post_id' => 0,
        ];
        $last_unread_post_res = $db->run(');
} else {
    $content .= '
        <tr>
            <td class="clear" colspan="8">
	            <span>' . _('No topics found') . '</span>
            </td>
		</tr>';
    $the_top_and_bottom = '';
}
$HTMLOUT .= $mini_menu . $sub_forums . "<h1 class='has-text-centered'>$forum_name</h1>" . ($count > $perpage ? $menu_top : '');
$heading = $body = '';
if (!empty($content)) {
    $heading = '
        <tr>
		    <th class="has-text-centered"><img src="' . $site_config['paths']['images_baseurl'] . 'forums/topic.gif" alt="' . _('Topic') . '" title="' . _('Topic') . '"  class="tooltipper icon"></th>
		    <th class="has-text-centered"><img src="' . $site_config['paths']['images_baseurl'] . 'forums/topic_normal.gif" alt=' . _('Thread Icon') . '" title=' . _('Thread Icon') . '"  class="tooltipper icon"></th>
		    <th class="has-text-centered">' . _('Topic') . '</th>
		    <th class="has-text-centered">' . _('Started By') . '</th>
		    <th class="has-text-centered">' . _('Replies') . '</th>
		    <th class="has-text-centered">' . _('Views') . '</th>
		    <th class="has-text-centered">' . _('Last Post') . '</th>
		    <th class="has-text-centered"><img src="' . $site_config['paths']['images_baseurl'] . 'forums/last_post.gif" alt="' . _('Last Post') . '" title="' . _('Last Post') . '" class="tooltipper icon"></th>
		</tr>';
}
$table = main_table($content, $heading);
$HTMLOUT .= $table . ($may_post ? '
                    <div class="has-text-centered margin20">
                        <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=new_topic&amp;forum_id=' . $forum_id . '" method="post" name="new" accept-charset="utf-8">
		                    <input type="hidden" name="action" value="new_topic">
		                    <input type="hidden" name="forum_id" value="' . $forum_id . '">
		                    <input type="submit" name="button" class="button is-small" value="' . _('New Topic') . '">
		                </form>
		            </div>' : '<span>' . _('You are not permitted to post in this forum.') . '</span>') . $the_top_and_bottom . ($count > $perpage ? $menu_bottom : '');

$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/forums.php'>" . _('Forums') . '</a>',
    "<a href='{$site_config['paths']['baseurl']}/forums.php?action=view_forum&forum_id={$forum_id}'>{$forum_name}</a>",
];
