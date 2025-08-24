<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

use Pu239\Cache;
use Pu239\Post;

$user = check_user_status();

$posted_staff_action = strip_tags((isset($_POST['action_2']) ? $_POST['action_2'] : ''));
$valid_staff_actions = [
    'delete_posts',
    'un_delete_posts',
    'split_topic',
    'merge_posts',
    'append_posts',
    'send_to_recycle_bin',
    'send_pm',
    'set_pinned',
    'set_locked',
    'move_topic',
    'rename_topic',
    'change_topic_desc',
    'merge_topic',
    'move_to_recycle_bin',
    'remove_from_recycle_bin',
    'delete_topic',
    'un_delete_topic',
];
$staff_action = in_array($posted_staff_action, $valid_staff_actions) ? $posted_staff_action : 1;
global $container;
$db = $container->get(Database::class);, $site_config;

if (!has_access($user['class'], UC_STAFF, 'coder')) {
    stderr(_('Error'), _('No access for you Mr. Fancy-Pants...'));
}
if ($staff_action === 1) {
    stderr(_('Error'), _('No action selected!'));
}
$post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
$topic_id = isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0;
$forum_id = isset($_POST['forum_id']) ? (int) $_POST['forum_id'] : 0;
if ($topic_id > 0) {
    $res_check = $db->run(');
                    $arr = mysqli_fetch_assoc($res);
                    if (empty($arr['id'])) {
                        $db->run(');
        }
        break;

    case 'un_delete_posts':
        if (isset($_POST['post_to_mess_with'])) {
            $_POST['post_to_mess_with'] = isset($_POST['post_to_mess_with']) ? $_POST['post_to_mess_with'] : '';
            $post_to_mess_with = [];
            foreach ($_POST['post_to_mess_with'] as $var) {
                $post_to_mess_with[] = (int) $var;
            }
            $post_to_mess_with = array_unique($post_to_mess_with);
            $posts_count = count($post_to_mess_with);
            if ($posts_count > 0) {
                $db->run(');
        }
        break;

    case 'split_topic':
        if (!is_valid_id($topic_id) || !is_valid_id($forum_id)) {
            stderr(_('Error'), _('Bad ID.'));
        }
        $new_topic_name = strip_tags(isset($_POST['new_topic_name']) ? trim($_POST['new_topic_name']) : '');
        $new_topic_desc = strip_tags(isset($_POST['new_topic_desc']) ? trim($_POST['new_topic_desc']) : '');
        if ($new_topic_name === '') {
            stderr(_('Error'), _('To split this topic, you must supply a name for the new topic!'));
        }
        if (isset($_POST['post_to_mess_with'])) {
            $db->run('INSERT INTO topics (topic_name, forum_id, topic_desc) VALUES (' . sqlesc($new_topic_name) . ', ' . sqlesc($forum_id) . ', ' . sqlesc($new_topic_desc) . ')') or sqlerr(__FILE__, __LINE__);
            $new_topic_id = ((is_null($___mysqli_res = mysqli_insert_id($mysqli))) ? false : $___mysqli_res);
            $_POST['post_to_mess_with'] = isset($_POST['post_to_mess_with']) ? $_POST['post_to_mess_with'] : '';
            $post_to_mess_with = [];
            foreach ($_POST['post_to_mess_with'] as $var) {
                $post_to_mess_with[] = (int) $var;
            }
            $post_to_mess_with = array_unique($post_to_mess_with);
            $posts_count = count($post_to_mess_with);
            if ($posts_count > 0) {
                $db->run(');
                $arr_split_from = mysqli_fetch_row($res_split_from);
                $db->run(');
                $arr_split_to = mysqli_fetch_row($res_split_to);
                $res_owner = $db->run(');
        }
        break;

    case 'merge_posts':
        $topic_to_merge_with = isset($_POST['new_topic']) ? (int) $_POST['new_topic'] : 0;
        $topic_res = $db->run(');
                $arr_from = mysqli_fetch_assoc($res_from);
                $db->run(');
                $arr_to = mysqli_fetch_assoc($res_to);
                $db->run(');
        }
        break;

    case 'append_posts':
        $topic_to_append_to = isset($_POST['new_topic']) ? (int) $_POST['new_topic'] : 0;
        $topic_res = $db->run(');
                $arr_from = mysqli_fetch_assoc($res_from);
                $db->run('UPDATE topics SET last_post = ' . sqlesc($arr_from['id']) . ', post_count = post_count - ' . sqlesc($count) . ' WHERE id = :id', [':id' => $topic_id]) or sqlerr(__FILE__, __LINE__);
                $db->run('UPDATE forums SET post_count = post_count - ' . sqlesc($count) . ' WHERE id=' . sqlesc($arr_from['forum_id'])) or sqlerr(__FILE__, __LINE__);
                $res_to = $db->run(');
        }
        break;

    case 'send_to_recycle_bin':
        if (isset($_POST['post_to_mess_with'])) {
            $_POST['post_to_mess_with'] = isset($_POST['post_to_mess_with']) ? $_POST['post_to_mess_with'] : '';
            $post_to_mess_with = [];
            foreach ($_POST['post_to_mess_with'] as $var) {
                $post_to_mess_with[] = intval($var);
            }
            $post_to_mess_with = array_unique($post_to_mess_with);
            $posts_count = count($post_to_mess_with);
            if ($posts_count > 0) {
                $db->run(');
        }
        break;

    case 'remove_from_recycle_bin':
        if (isset($_POST['post_to_mess_with'])) {
            $_POST['post_to_mess_with'] = isset($_POST['post_to_mess_with']) ? $_POST['post_to_mess_with'] : '';
            $post_to_mess_with = [];
            foreach ($_POST['post_to_mess_with'] as $var) {
                $post_to_mess_with[] = intval($var);
            }
            $post_to_mess_with = array_unique($post_to_mess_with);
            $posts_count = count($post_to_mess_with);
            if ($posts_count > 0) {
                $db->run(');
        }
        break;

    case 'send_pm':
        if (!is_valid_id($topic_id)) {
            stderr(_('Error'), _('Bad ID.'));
        }
        $subject = strip_tags(isset($_POST['subject']) ? trim($_POST['subject']) : '');
        $message = (isset($_POST['message']) ? htmlsafechars($_POST['message']) : '');
        $from = ((isset($_POST['pm_from']) && $_POST['pm_from'] == 0) ? 2 : $user['id']);
        if ($subject == '' || $message == '') {
            stderr(_('Error'), _('You must enter both a subject and message.'));
        }
        if (isset($_POST['post_to_mess_with'])) {
            $_POST['post_to_mess_with'] = (isset($_POST['post_to_mess_with']) ? $_POST['post_to_mess_with'] : '');
            $post_to_mess_with = [];
            $count = 0;
            foreach ($_POST['post_to_mess_with'] as $var) {
                $post_to_mess_with = intval($var);
                $post_res = $db->run(');
                $count = $count + 1;
            }
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id . '&count=' . $count);
        app_halt('Exit called');
        break;

    case 'set_pinned':
        if (!is_valid_id($topic_id)) {
            stderr(_('Error'), _('Bad ID.'));
        }
        $db->run('UPDATE topics SET sticky = "' . ($_POST['pinned'] === 'yes' ? 'yes' : 'no') . '" WHERE id = :id', [':id' => $topic_id]) or sqlerr(__FILE__, __LINE__);
        clr_forums_cache($topic_id);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id);
        app_halt('Exit called');
        break;

    case 'set_locked':
        if (!is_valid_id($topic_id)) {
            stderr(_('Error'), _('Bad ID.'));
        }
        $db->run('UPDATE topics SET locked = "' . ($_POST['locked'] === 'yes' ? 'yes' : 'no') . '" WHERE id = :id', [':id' => $topic_id]) or sqlerr(__FILE__, __LINE__);
        clr_forums_cache($topic_id);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id);
        app_halt('Exit called');
        break;

    case 'move_topic':
        $rows = $db->fetchAll('SELECT id FROM forums WHERE id=' . sqlesc($forum_id)) or sqlerr(__FILE__, __LINE__);
        $arr = mysqli_fetch_row($res);

        if (!is_valid_id((int) $arr[0])) {
            stderr(_('Error'), _('Bad ID.'));
        }
        $db->run('UPDATE topics SET forum_id=' . sqlesc($forum_id) . ' WHERE id = :id', [':id' => $topic_id]) or sqlerr(__FILE__, __LINE__);
        clr_forums_cache($topic_id);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id);
        app_halt('Exit called');
        break;

    case 'rename_topic':
        $new_topic_name = strip_tags((isset($_POST['new_topic_name']) ? trim($_POST['new_topic_name']) : ''));
        if ($new_topic_name === '') {
            stderr(_('Error'), _('If you want to rename the topic, you must supply a name!'));
        }
        $db->run('UPDATE topics SET topic_name = ' . sqlesc($new_topic_name) . ' WHERE id = :id', [':id' => $topic_id]) or sqlerr(__FILE__, __LINE__);
        clr_forums_cache($topic_id);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id);
        app_halt('Exit called');
        break;

    case 'change_topic_desc':
        $new_topic_desc = strip_tags((isset($_POST['new_topic_desc']) ? trim($_POST['new_topic_desc']) : ''));
        $db->run('UPDATE topics SET topic_desc = ' . sqlesc($new_topic_desc) . ' WHERE id = :id', [':id' => $topic_id]) or sqlerr(__FILE__, __LINE__);
        clr_forums_cache($topic_id);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id);
        app_halt('Exit called');
        break;

    case 'merge_topic':
        $topic_to_merge_with = (isset($_POST['topic_to_merge_with']) ? (int) $_POST['topic_to_merge_with'] : 0);
        $topic_res = $db->run(');
        $arr = mysqli_fetch_assoc($res);
        $db->run(');
        break;

    case 'move_to_recycle_bin':
        $status = $_POST['status'] === 'yes' ? 'recycled' : 'ok';
        $db->run(');
        break;

    case 'delete_topic':
        if (!isset($_POST['sanity_check'])) {
            stderr(_('Sanity Check!'), '' . _('Are you sure you want to delete this topic? If you are sure, click the delete button.') . '<br>
	<form action="forums.php?action=staff_actions" method="post" accept-charset="utf-8">
	<input type="hidden" name="action_2" value="delete_topic">
	<input type="hidden" name="sanity_check" value="1">
	<input type="hidden" name="topic_id" value="' . $topic_id . '">
	<input type="submit" name="button" class="top20 button is-small" value="' . _('Delete Topic') . '">
	</form>');
        }
        if ($site_config['forum_config']['delete_for_real']) {
            $db->run('UPDATE topics SET status = "deleted" WHERE id = :id', [':id' => $topic_id]) or sqlerr(__FILE__, __LINE__);
            header('Location: ' . $_SERVER['PHP_SELF']);
            app_halt('Exit called');
        } else {
            $res_count = $db->run(');
        }
        break;

    case 'un_delete_topic':
        $db->run(');
        break;
}
