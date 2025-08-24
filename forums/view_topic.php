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
$arr = $fluent->from('topics AS t')
              ->select(null)
              ->select('t.id AS topic_id')
              ->select('t.user_id')
              ->select('t.topic_name')
              ->select('t.locked')
              ->select('t.last_post')
              ->select('t.sticky')
              ->select('t.status')
              ->select('t.views')
              ->select('t.poll_id')
              ->select('t.num_ratings')
              ->select('t.rating_sum')
              ->select('t.topic_desc')
              ->select('t.forum_id')
              ->select('t.anonymous')
              ->select('t.user_likes')
              ->select('f.name AS forum_name')
              ->select('f.min_class_read')
              ->select('f.min_class_write')
              ->select('f.parent_forum')
              ->innerJoin('forums AS f ON t.forum_id=f.id')
              ->where('t.id = ?', $topic_id);
if (!has_access($CURUSER['class'], UC_STAFF, 'forum_mod')) {
    $arr = $arr->where('t.status = "ok"');
}
if (!has_access($CURUSER['class'], $site_config['forum_config']['min_delete_view_class'], 'forum_mod')) {
    $arr = $arr->where('t.status != "deleted"');
}
$arr = $arr->fetch();
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
    $arr_poll = $fluent->from('forum_poll')
                       ->where('id = ?', $arr['poll_id'])
                       ->fetch();
    if (!empty($arr_poll)) {
        if (has_access($CURUSER['class'], UC_STAFF, '')) {
            $query = $fluent->from('forum_poll_votes')
                            ->where('forum_poll_votes.id>0')
                            ->where('poll_id = ?', $arr['poll_id']);
            $who_voted = $query ? '<hr>' : 'no votes yet';
            foreach ($query as $arr_poll_voted) {
                $who_voted .= format_username((int) $arr_poll_voted['user_id']);
            }
        }

        $query = $fluent->from('forum_poll_votes')
                        ->select(null)
                        ->select('options')
                        ->where('poll_id = ?', $arr['poll_id'])
                        ->where('user_id = ?', $CURUSER['id'])
                        ->fetchAll();

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
        $total_votes = $fluent->from('forum_poll_votes')
                              ->select(null)
                              ->select('COUNT(id) AS count')
                              ->where('options < 21')
                              ->where('poll_id = ?', $arr['poll_id'])
                              ->fetch('count');

        $num_non_votes = $fluent->from('forum_poll_votes')
                                ->select(null)
                                ->select('COUNT(id) AS count')
                                ->where('options > 20')
                                ->where('poll_id = ?', $arr['poll_id'])
                                ->fetch('count');

        $total_non_votes = $num_non_votes > 0 ? ' [ ' . _pfe('{0} member just wanted to see the results', '{0} members just wanted to see the results', number_format($num_non_votes)) . ' ]' : '';
        $topic_poll .= ($voted || $poll_open === 0 ? '' : '
    <form action="' . $site_config['paths']['baseurl'] . '/forums.php?action=poll" method="post" name="poll" accept-charset="utf-8">
        <input type="hidden" name="topic_id" value="' . $topic_id . '">
        <input type="hidden" name="action_2" value="poll_vote">') . '
        <div class="level-wide bottom20 padding20">
            <div class="level-left">
                <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/poll.gif" alt="" class="tooltipper emoticon lazy">
            </div>
            <div class="level-center-center size_7">
                <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/poll_question.png" alt="" class="tooltipper emoticon lazy right20">           
                ' . format_comment($arr_poll['question']) . '
            </div>
            <div class="level-right">
                <span class="right20">' . ($arr_poll['poll_closed'] === 'yes' ? 'Poll :: Closed</span>' : ($arr_poll['poll_starts'] > TIME_NOW ? 'Poll :: Starts: </span>' . get_date((int) $arr_poll['poll_starts'], '') : ($arr_poll['poll_ends'] == 1356048000 ? '</span>' : ($arr_poll['poll_ends'] > TIME_NOW ? 'Poll :: Ends: </span>' . get_date((int) $arr_poll['poll_ends'], 'LONG', 0, 1) : '</span>')))) . ($CURUSER['class'] < UC_STAFF ? '' : '
                    <a href="' . $site_config['paths']['baseurl'] . '/forums.php?action=poll&amp;action_2=poll_edit&amp;topic_id=' . $topic_id . '" class="is-link">
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/modify.gif" alt="" class="tooltipper emoticon lazy" title="' . _('Edit') . '">
                    </a>
                    <a href="' . $site_config['paths']['baseurl'] . '/forums.php?action=poll&amp;action_2=poll_reset&amp;topic_id=' . $topic_id . '" class="is-link">
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/stop_watch.png" alt=" " class="tooltipper emoticon lazy" title="' . _('Reset') . '">
                    </a>' . (($arr_poll['poll_ends'] > TIME_NOW || $arr_poll['poll_closed'] === 'no') ? '
                    <a href="' . $site_config['paths']['baseurl'] . '/forums.php?action=poll&amp;action_2=poll_close&amp;topic_id=' . $topic_id . '" class="is-link">
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/clock.png" alt="" class="emoticon lazy" title="Close">
                    </a>' : '
                    <a href="' . $site_config['paths']['baseurl'] . '/forums.php?action=poll&amp;action_2=poll_open&amp;topic_id=' . $topic_id . '" class="is-link">
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/clock.png" alt="" class="emoticon lazy" title="' . _('Start') . '">
                    </a>') . '
                    <a href="' . $site_config['paths']['baseurl'] . '/forums.php?action=poll&amp;action_2=poll_delete&amp;topic_id=' . $topic_id . '" class="is-link">
                        <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/delete.gif" alt="" class="tooltipper emoticon lazy" title="' . _('Delete') . '">
                    </a>') . '
                </span>
            </div>
        </div>' . (($voted || $poll_open === 0) ? '' : '
        <div class="has-text-centered bottom20 bg-02 min-350 w-50 round10 padding20">
            <h3 class="bottom20">You may select up to ' . $multi_options . ' option' . plural($multi_options) . '.</h3>');
        $number_of_options = $arr_poll['number_of_options'];
        for ($i = 0; $i < $number_of_options; ++$i) {
            if ($voted) {
                $vote_count = $fluent->from('forum_poll_votes')
                                     ->select(null)
                                     ->select('COUNT(id) AS count')
                                     ->where('options = ?', $i)
                                     ->where('poll_id = ?', $arr['poll_id'])
                                     ->fetch('count');

                $math = $vote_count > 0 ? round(($vote_count / $total_votes) * 100) : 0;
                $math_text = $math . '% with ' . $vote_count . ' vote' . plural($vote_count);
                $math_image = '
            <div style="padding: 0; background-image: url(' . $site_config['paths']['images_baseurl'] . '/forums/vote_img_bg.gif); background-repeat: repeat-x">
                <span class="tooltipper" title="' . $math_text . '">
                    <i class="icon-search icon" aria-hidden="true"></i>
                </span>
            </div>';
            }
            $topic_poll .= ($voted || $poll_open === 0 ? '' : '
            <span class="level-center-center padding10">
                <span class="right20">' . ($multi_options === 1 ? '
                    <input type="radio" name="vote" value="' . $i . '" class="right10">' : '
                    <input type="checkbox" name="vote[]" id="vote[]" value="' . $i . '" class="right10"> ') . ($i + 1) . '.
                </span>
                <span>' . format_comment($poll_options[$i]) . $math_image . $math_text . (in_array($i, $members_votes) ? '
                    <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/check.gif" alt=" " class="tooltipper emoticon lazy">' . _('Your vote') . '!' : '') . '
                </span>
            </span>');
        }
        $topic_poll .= ($change_vote === 1 && $voted ? '
            <a href="' . $site_config['paths']['baseurl'] . '/forums.php?action=poll&amp;action_2=reset_vote&amp;topic_id=' . $topic_id . '" class="is-link">
                <img src="' . $image . '" data-src="' . $site_config['paths']['images_baseurl'] . 'forums/stop_watch.png" alt="" class="tooltipper emoticon lazy"> ' . _('Reset Your Vote') . '!
            </a>' : '') . ($voted ? _('Total votes') . ': ' . number_format($total_votes) . $total_non_votes . ($CURUSER['class'] < UC_STAFF ? '' : '<br>
            <a class="is-link"  title="' . _('List voters') . '" id="toggle_voters">' . _('List voters') . '</a>
            <div id="voters" style="display:none">' . $who_voted . '</div>') : ($poll_open === 0 ? '' : '
            <div class="margin20">' . ($multi_options === 1 ? '
                <input type="radio" name="vote" value="666">' : '
                <input type="checkbox" name="vote[]" id="vote[]" value="666">') . '
                <span class="left10">' . _('I just want to see the results') . '!</span>
            </div>') . ($voted || $poll_open === 0 ? '' : '
            <div class="has-text-centered">
                <input type="submit" name="button" class="button is-small" value="' . _('Vote') . '!">
            </div>'));

        $topic_poll .= ($voted || $poll_open === 0 ? '' : '
        </div>') . '
    </form>';
    }
}
$topic_poll = main_div($topic_poll, '', 'has-text-centered padding20');
if (isset($_GET['search'])) {
    $search = htmlsafechars($_GET['search']);
    $topic_name = highlightWords($topic_name, $search);
}
$forum_desc = (!empty($arr['topic_desc']) ? '<span>' . htmlsafechars($arr['topic_desc']) . '</span>' : '');
$locked = ($arr['locked'] === 'yes' ? 'yes' : 'no');
$sticky = ($arr['sticky'] === 'yes' ? 'yes' : 'no');
$views = number_format($arr['views']);

$forum_name = htmlsafechars($arr['forum_name']);

if ($arr['num_ratings'] != 0) {
    $rating = round($arr['rating_sum'] / $arr['num_ratings'], 1);
}

$subscribed = $fluent->from('subscriptions')
                     ->select(null)
                     ->select('id')
                     ->where('topic_id = ?', $topic_id)
                     ->where('user_id = ?', $CURUSER['id'])
                     ->fetch('id');

$subscriptions = $subscribed ? "<a href='{$site_config['paths']['baseurl']}/forums.php?action=delete_subscription&amp;topic_id={$topic_id}'>" . _('Unsubscribe from this topic') . '</a>' : "
        <a href='{$site_config['paths']['baseurl']}/forums.php?action=add_subscription&amp;forum_id={$forum_id}&amp;topic_id={$topic_id}'>" . _('Subscribe to this topic') . '</a>';

$values = [
    'user_id' => $CURUSER['id'],
    'forum_id' => $forum_id,
    'topic_id' => $topic_id,
    'added' => TIME_NOW,
];
$fluent->deleteFrom('now_viewing')
       ->where('user_id = ?', $CURUSER['id'])
       ->execute();
if (!get_anonymous($CURUSER['id'])) {
    $fluent->insertInto('now_viewing')
           ->values($values)
           ->execute();
}
$cache = $container->get(Cache::class);
$topic_users_cache = $cache->get('now_viewing_topic_');
if ($topic_users_cache === false || is_null($topic_users_cache)) {
    $topicusers = '';
    $topic_users_cache = [];
    $query = $fluent->from('now_viewing')
                    ->select(null)
                    ->select('now_viewing.user_id')
                    ->select('users.perms')
                    ->innerJoin('users ON now_viewing.user_id=users.id')
                    ->where('topic_id = ?', $topic_id)
                    ->where('users.anonymous_until < ?', TIME_NOW)
                    ->where('users.perms < ?', PERMS_STEALTH)
                    ->where('users.paranoia < ?', 2);

    foreach ($query as $row) {
        $list[] = format_username((int) $row['user_id']);
    }

    $topicusers = empty($list) ? '' : implode(',&nbsp;&nbsp;', $list);
    $topic_users_cache['topic_users'] = $topicusers;
    $topic_users_cache['actcount'] = empty($list) ? 0 : count($list);
    $cache->set('now_viewing_topic_', $topic_users_cache, $site_config['expires']['forum_users']);
}
if (!$topic_users_cache['topic_users']) {
    $topic_users_cache['topic_users'] = _('There have been no active users in the last 15 minutes.');
}

$topic_users = $topic_users_cache['topic_users'];
if (!empty($topic_users)) {
    $topic_users = _('Currently viewing this topic') . ': ' . $topic_users;
}

$set = [
    'views' => new Literal('views + 1'),
];
$fluent->update('topics')
       ->set($set)
       ->where('id = ?', $topic_id)
       ->execute();

$res_count = $db->run(');
    $attachments = '';
}

$fluent->deleteFrom('read_posts')
       ->where('user_id = ?', $CURUSER['id'])
       ->where('topic_id = ?', $topic_id)
       ->execute();

$values = [
    'user_id' => $CURUSER['id'],
    'topic_id' => $topic_id,
    'last_post_read' => $postid,
];
$update = [
    'last_post_read' => $postid,
];
$fluent->insertInto('read_posts', $values)
       ->onDuplicateKeyUpdate($update)
       ->execute();
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
