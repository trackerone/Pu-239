<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);
require_once __DIR__ . '/../include/bittorrent.php';
require_once FORUM_DIR . 'quick_reply.php';
require_once INCL_DIR . 'function_users.php';

use Envms\FluentPDO\Literal;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Mood;
use Pu239\Session;
use Pu239\User;

$user = check_user_status();
$image = placeholder_image();
$status = $topic_poll = $stafflocked = $child = $parent_forum_name = $math_image = $math_text = $now_viewing = '';
$members_votes = [];
$topic_id = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0);
if (!is_valid_id($topic_id)) {
    stderr(_('Error'), _('Invalid ID.'));
}

$upload_errors_size = isset($_GET['se']) ? (int) $_GET['se'] : 0;
$upload_errors_type = isset($_GET['ee']) ? (int) $_GET['ee'] : 0;
global $container;
$db = $container->get(Database::class);, $site_config, $CURUSER;

$_forum_sort = isset($CURUSER['forum_sort']) ? $CURUSER['forum_sort'] : 'DESC';
$fluent = $db; // alias
$fluent = $container->get(Database::class);
$arr = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
if (empty($arr) || !has_access($CURUSER['class'], $arr['min_class_read'], '') || !is_valid_id($arr['topic_id']) || !has_access($CURUSER['class'], $site_config['forum_config']['min_delete_view_class'], '') && $status === 'deleted' || !has_access($CURUSER['class'], UC_STAFF, '') && $status === 'recycled') {
    stderr(_('Error'), _('Invalid ID.'));
}

$status = htmlsafechars($arr['status']);
switch ($status) {
    case 'ok':
        $status = '';
        $status_image = '';
        break;

    case 'recycled':
        $status = 'recycled';
        $status_image = '<img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/recycle_bin.gif" alt="' . _('Recycled') . '" title="' . _('This thread is currently') . ' ' . _('in the recycle-bin') . '" class="tooltipper emoticon lazy">';
        break;

    case 'deleted':
        $status = 'deleted';
        $status_image = '<img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/delete_icon.gif" alt="' . _('Deleted') . '" title="' . _('This thread is currently') . ' ' . _('Deleted') . '" class="tooltipper emoticon lazy">';
        break;
}

$forum_id = $arr['forum_id'];
$topic_owner = $arr['anonymous'] === '1' ? get_anonymous_name() : format_username($arr['user_id']);
$topic_name = !empty($arr['topic_name']) ? format_comment($arr['topic_name']) : '';
$topic_desc1 = !empty($arr['topic_desc']) ? format_comment($arr['topic_desc']) : '';

if ($arr['poll_id'] > 0) {
    $arr_poll = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
    if (!empty($arr_poll)) {
        if (has_access($CURUSER['class'], UC_STAFF, '')) {
            $query = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        $voted = 0;
        $members_vote = 1000;
        if ($query) {
            $voted = 1;
            foreach ($query as $members_vote) {
                $members_votes[] = $members_vote['options'];
            }
        }
        $change_vote = $arr_poll['change_vote'] === 'no' ? 0 : 1;
        $poll_open = $arr_poll['poll_closed'] === 'yes' || $arr_poll['poll_starts'] > TIME_NOW || ($arr_poll['poll_ends'] != 1356048000 && $arr_poll['poll_ends'] < TIME_NOW) ? 0 : 1;
        $poll_options = json_decode($arr_poll['poll_answers'], true);
        $multi_options = $arr_poll['multi_options'];
        $total_votes = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
if (!get_anonymous($CURUSER['id'])) {
    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
}
$cache = $container->get(Cache::class);
$topic_users_cache = $cache->get('now_viewing_topic_');
if ($topic_users_cache === false || is_null($topic_users_cache)) {
    $topicusers = '';
    $topic_users_cache = [];
    $query = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

$res_count = $db->run(');
    $attachments = '';
}

// TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

$values = [
    'user_id' => $CURUSER['id'],
    'topic_id' => $topic_id,
    'last_post_read' => $postid,
];
$update = [
    'last_post_read' => $postid,
];
// TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
$cache->delete('last_read_post_' . $topic_id . '_' . $CURUSER['id']);
$cache->delete('sv_last_read_post_' . $topic_id . '_' . $CURUSER['id']);
$HTMLOUT .= '
    </table>' . ($posts_count > $perpage ? $menu_bottom : '') . '
    <a id="bottom"></a>
    <br>';

if ($CURUSER['class'] >= UC_STAFF) {
    $HTMLOUT .= '
    <div class="level-center margin20">
        <span class="level-center">
            <a class="is-link flipper"  title="' . _('Staff Tools') . '" id="staff_tools_open">
				<i class="icon-up-open size_2" aria-hidden="true"></i>' . _('Staff Tools') . '
			</a>
        </span>
    </div>
    <div id="staff_tools" style="display:none" class="bottom20">';
    $table = '
        <tr>
            <td>
                <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/merge.gif" alt="' . _('Merge') . '" title="' . _('Merge') . '" class="tooltipper emoticon lazy">
            </td>
            <td>
                <input type="radio" name="action_2" value="merge_posts">' . _('Merge With') . '<br>
                <input type="radio" name="action_2" value="append_posts">' . _('Append To') . '
            </td>
            <td>
                ' . _('Topic') . ':<input type="text" size="2" name="new_topic" value="' . $topic_id . '">
            </td>
            <td class="has-text-centered">
                <div class="bottom10">
                    <input type="checkbox" id="checkThemAll" class="tooltipper" title="Select All"> Select All
                </div>
                <input type="submit" name="button" class="button is-small w-100" value="' . _('With Selected') . '">
            </td>
        </tr>
        <tr>
            <td>
                <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/split.gif" alt="' . _('Split') . '" title="' . _('Split') . '" class="tooltipper emoticon lazy">
            </td>
            <td>
                <input type="radio" name="action_2" value="split_topic">' . _('Split Topic') . '
            </td>
            <td>
                ' . _('New Topic Name') . ':<input type="text" size="20" maxlength="120" name="new_topic_name" value="' . (!empty($topic_name) ? $topic_name : '') . '"> [required]<br>
                ' . _('New Topic Desc') . ':<input type="text" size="20" maxlength="120" name="new_topic_desc" value="">
            </td>
            <td class="has-text-centered">
                <input type="submit" name="button" class="button is-small w-100" value="Fixit!">
            </td>
        </tr>
        <tr>
            <td>
                <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/send_pm.png" alt="' . _('Send Message') . '" title="' . _('Send Message') . '" class="tooltipper emoticon lazy">
            </td>
            <td colspan="2">
                <div id="pm" style="display:none">' . main_table('
                    <tr>
                        <td colspan="2">' . _('Send PM to Selected Members') . '</td>
                    </tr>
                    <tr>
                        <td>
                            <span>' . _('Subject') . ':</span>
                        </td>
                        <td>
                            <input type="text" size="20" maxlength="120" class="w-100" name="subject" value="">
                            <input type="radio" name="action_2" value="send_pm">
                            <span>' . _('Select to send') . '.</span> 
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span>' . _('Message') . ':</span>
                        </td>
                        <td>
                            <textarea cols="30" rows="4" name="message" class="text_area_small"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span>' . _('From') . ':</span>
                        </td>
                        <td>
                            <input type="radio" name="pm_from" value="0" checked> ' . _('System') . '
                            <input type="radio" name="pm_from" value="1"> ' . format_username((int) $CURUSER['id']) . '
                        </td>
                    </tr>', '', 'top20') . '
                </div>
            </td>
            <td class="has-text-centered">
                <a class="button is-small w-100" title="' . _('Send PM to Selected Members') . '" id="pm_open">' . _('Send PM') . '</a>
            </td>
        </tr>
                <tr>
                    <td>
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/pinned.gif" alt="' . _('Pinned') . '" title="' . _('Pinned') . '" class="tooltipper emoticon lazy">
                    </td>
                    <td>
                        <span>' . _('Pin') . ' ' . _('Topic') . ':</span>
                    </td>
                    <td>
                        <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=staff_actions" method="post" accept-charset="utf-8">
                            <input type="hidden" name="action_2" value="set_pinned">
                            <input type="hidden" name="topic_id" value="' . $topic_id . '">
                            <input type="radio" name="pinned" value="yes" ' . ($sticky === 'yes' ? 'checked' : '') . '> Yes
                            <input type="radio" name="pinned" value="no" ' . ($sticky === 'no' ? 'checked' : '') . '> No
                    </td>
                    <td class="has-text-centered">
                            <input type="submit" name="button" class="button is-small w-100" value="Set ' . _('Pinned') . '">
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/thread_locked.gif" alt="' . _('Locked') . '" title="' . _('Locked') . '" class="tooltipper emoticon lazy">
                    </td>
                    <td>
                        <span>' . _('Lock') . ' ' . _('Topic') . ':</span>
                    </td>
                    <td>
                        <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=staff_actions" method="post" accept-charset="utf-8">
                            <input type="hidden" name="action_2" value="set_locked">
                            <input type="hidden" name="topic_id" value="' . $topic_id . '">
                            <input type="radio" name="locked" value="yes" ' . ($locked === 'yes' ? 'checked' : '') . '> Yes
                            <input type="radio" name="locked" value="no" ' . ($locked === 'no' ? 'checked' : '') . '> No
                    </td>
                    <td class="has-text-centered">
                            <input type="submit" name="button" class="button is-small w-100" value="' . _('Lock Topic') . '">
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>

                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/move.gif" alt="' . _('Move') . '" title="' . _('Move') . '" class="tooltipper emoticon lazy">
                    </td>
                    <td>
                        <span>' . _('Move') . ' ' . _('Topic') . ':</span>
                    </td>
                    <td>
                        <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=staff_actions" method="post" accept-charset="utf-8">
                            <input type="hidden" name="action_2" value="move_topic">
                            <input type="hidden" name="topic_id" value="' . $topic_id . '">
                            <select name="forum_id">
                                ' . insert_quick_jump_menu($forum_id, true) . '
                            </select>
                    </td>
                    <td class="has-text-centered">
                            <input type="submit" name="button" class="button is-small w-100" value="' . _('Move Topic') . '">
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/modify.gif" alt="' . _('Modify') . '" title="' . _('Modify') . '" class="tooltipper emoticon lazy">
                    </td>
                    <td>
                        <span>' . _('Rename') . ' ' . _('Topic') . ':</span>
                    </td>
                    <td>
                        <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=staff_actions" method="post" accept-charset="utf-8">
                            <input type="hidden" name="action_2" value="rename_topic">
                            <input type="hidden" name="topic_id" value="' . $topic_id . '">
                            <input type="text" size="40" maxlength="120" name="new_topic_name" value="' . (!empty($topic_name) ? $topic_name : '') . '">
                    </td>
                    <td class="has-text-centered">
                            <input type="submit" name="button" class="button is-small w-100" value="' . _('Rename Topic') . '">
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/modify.gif" alt="' . _('Modify') . '" title="' . _('Modify') . '" class="tooltipper emoticon lazy">
                    </td>
                    <td>
                        <span>' . _('Change Topic Desc') . ':</span>
                    </td>
                    <td>
                        <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=staff_actions" method="post" accept-charset="utf-8">
                            <input type="hidden" name="action_2" value="change_topic_desc">
                            <input type="hidden" name="topic_id" value="' . $topic_id . '">
                            <input type="text" size="40" maxlength="120" name="new_topic_desc" value="' . (!empty($topic_desc1) ? $topic_desc1 : '') . '"></td>
                    <td class="has-text-centered">
                            <input type="submit" name="button" class="button is-small w-100" value="' . _('Change Desc') . '">
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/merge.gif" alt="' . _('Merge') . '" title="' . _('Merge Topic') . '" class="tooltipper emoticon lazy">
                    </td>
                    <td>
                        <span>' . _('Merge') . ' ' . _('Topic') . ':</span>
                    </td>
                    <td>' . _('With topic #') . '
                        <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=staff_actions" method="post" accept-charset="utf-8">
                            <input type="hidden" name="action_2" value="merge_topic">
                            <input type="hidden" name="topic_id" value="' . $topic_id . '">
                            <input type="text" size="4" name="topic_to_merge_with" value="' . $topic_id . '">
                            <p>' . _('Enter the destination  Topic Id to merge into') . '<br>
                            ' . _('Topic ID can be found in the address bar above... the topic id for this thread is:') . ' ' . $topic_id . '</p>
                            <p>' . _('This option will mix the two topics together, keeping dates and post numbers preserved.') . '</p>
                    </td>
                    <td class="has-text-centered">
                            <input type="submit" name="button" class="button is-small w-100" value="' . _('Merge Topic') . '">
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/merge.gif" alt="' . _('Append') . '" title="' . _('Append Topic') . '" class="tooltipper emoticon lazy">
                    </td>
                    <td>
                        <span>' . _('Append') . ' ' . _('Topic') . ':</span>
                    </td>
                    <td>' . _('With topic #') . '
                        <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=staff_actions" method="post" accept-charset="utf-8">
                            <input type="hidden" name="action_2" value="append_topic">
                            <input type="hidden" name="topic_id" value="' . $topic_id . '">
                            <input type="text" size="4" name="topic_to_append_into" value="' . $topic_id . '">
                            <p>' . _('Enter the destination  Topic Id to append to.') . '<br>
                            ' . _('Topic ID can be found in the address bar above... the topic id for this thread is:') . ' ' . $topic_id . '</p>
                            <p>' . _('This option will append this topic to the end of the new topic. The dates will be preserved, but the posts will be added after the last post in the appended to thread.') . '</p>
                     </td>
                     <td class="has-text-centered">
                            <input type="submit" name="button" class="button is-small w-100" value="' . _('Append Topic') . '">
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/recycle_bin.gif" alt="' . _('Recycle') . '" title="' . _('Recycle') . '" class="tooltipper emoticon lazy"></td>
                    <td>
                        <span>' . _('Move to Recycle Bin') . ':</span>
                    </td>
                    <td>
                        <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=staff_actions" method="post" accept-charset="utf-8">
                            <input type="hidden" name="action_2" value="move_to_recycle_bin">
                            <input type="hidden" name="topic_id" value="' . $topic_id . '">
                            <input type="hidden" name="forum_id" value="' . $forum_id . '">
                            <input type="radio" name="status" value="yes" ' . ($status === 'recycled' ? 'checked' : '') . '> Yes
                            <input type="radio" name="status" value="no" ' . ($status !== 'recycled' ? 'checked' : '') . '> No<br>
                            ' . _('This option will send this thread to the hidden recycle bin for other staff to view it.') . '<br>
                            ' . _('All subscriptions to this thread will be deleted!') . '
                    </td>
                    <td class="has-text-centered">
                            <input type="submit" name="button" class="button is-small w-100" value="' . _('Recycle It') . '">
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/delete.gif" alt="' . _('Delete') . '" title="' . _('Delete') . '" class="tooltipper emoticon lazy"></td>
                    <td>
                        <span>' . _('Delete Topic') . ':</span>
                    </td>
                    <td>' . _('Are you really sure you want to delete this topic, and not just move it or merge it?') . '</td>
                    <td class="has-text-centered">
                        <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=staff_actions" method="post" accept-charset="utf-8">
                            <input type="hidden" name="action_2" value="delete_topic">
                            <input type="hidden" name="topic_id" value="' . $topic_id . '">
                            <input type="submit" name="button" class="button is-small w-100" value="' . _('Delete Topic') . '">
                        </form>
                    </td>
                </tr>' . ($CURUSER['class'] < $site_config['forum_config']['min_delete_view_class'] ? '' : '
                <tr>
                    <td>
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/delete_icon.gif" alt="' . _('Un-Delete Topic') . '" title="' . _('Un-Delete Topic') . '" class="tooltipper emoticon lazy">
                    </td>
                    <td>
                        <span>
                            <span class="has-text-danger">*</span>' . _('Un-Delete Topic') . ':
                        </span>
                    </td>
                    <td></td>
                    <td class="has-text-centered">
                        <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=staff_actions" method="post" accept-charset="utf-8">
                            <input type="hidden" name="action_2" value="un_delete_topic">
                            <input type="hidden" name="topic_id" value="' . $topic_id . '">
                            <input type="submit" name="button" class="button is-small w-100" value="' . _('Un-Delete Topic') . '">
                        </form>
                    </td>
                </tr>
                <tr>
                    <td class="has-text-centered" colspan="4">
                        <span class="has-text-danger">*</span>only <span>' . get_user_class_name((int) $site_config['forum_config']['min_delete_view_class']) . '</span> ' . _('and above can see these options!') . '
                    </td>
                </tr>');
    $HTMLOUT .= main_table($table) . '
        </form>
    </div>';
}
$HTMLOUT .= quick_reply($topic_id);

$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/forums.php'>" . _('Forums') . '</a>',
    "<a href='{$site_config['paths']['baseurl']}/forums.php?action=view_forum&forum_id={$forum_id}'>{$forum_name}</a>",
    "<a href='{$site_config['paths']['baseurl']}/forums.php?action=view_topic&topic_id={$topic_id}'>{$topic_name}</a>",
];
