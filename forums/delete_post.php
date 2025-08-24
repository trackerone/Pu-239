<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Database;

global $container;
$db = $container->get(Database::class);, $site_config, $CURUSER;

$post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : (isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0);
$topic_id = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0);
$sanity_check = isset($_GET['sanity_check']) ? (int) $_GET['sanity_check'] : 0;
if (!is_valid_id($post_id) || !is_valid_id($topic_id)) {
    stderr(_('Error'), _('Bad ID.'));
}
$fluent = $db; // alias
$fluent = $container->get(Database::class);
$arr_post = $fluent->from('posts AS p')
                   ->select(null)
                   ->select('p.user_id')
                   ->select('p.staff_lock')
                   ->select('u.class')
                   ->select('u.status')
                   ->select('t.locked')
                   ->select('t.user_id as owner_id')
                   ->select('t.first_post')
                   ->select('f.min_class_read')
                   ->select('f.min_class_write')
                   ->select('f.id AS forum_id')
                   ->leftJoin('users AS u ON p.user_id = u.id')
                   ->leftJoin('topics AS t ON p.topic_id = t.id')
                   ->leftJoin('forums AS f ON t.forum_id = f.id')
                   ->where('p.id = ?', $post_id)
                   ->fetch();
$can_delete = $arr_post['user_id'] === $CURUSER['id'] || has_access($CURUSER['class'], UC_STAFF, 'forum_mod');
if (!has_access($CURUSER['class'], (int) $arr_post['min_class_read'], '') || !has_access($CURUSER['class'], (int) $arr_post['min_class_write'], '')) {
    stderr(_('Error'), _('Topic not found.'));
}
if ($CURUSER['forum_post'] === 'no' || $CURUSER['status'] !== 0) {
    stderr(_('Error'), _('Your posting rights have been suspended.'));
}
if (!$can_delete) {
    stderr(_('Error'), _('This is not your post to delete.'));
}
if ($arr_post['locked'] === 'yes') {
    stderr(_('Error'), _('This topic is locked'));
}
if ($arr_post['staff_lock'] === 1) {
    stderr(_('Error'), _('This post staff is locked my friend, deleting the evidence you wont be.'));
}
if ($arr_post['first_post'] == $post_id && $CURUSER['class'] < UC_STAFF) {
    stderr(_('Error'), _('This is the first post in the topic, only Staff can delete topics.'));
}
if ($arr_post['first_post'] == $post_id && $CURUSER['class'] >= UC_STAFF) {
    $delete = "
    	<form action='forums.php?action=staff_actions' method='post' accept-charset='utf-8'>
	        <input type='hidden' name='action_2' value='delete_topic'>
	        <input type='hidden' name='sanity_check' value='1'>
	        <input type='hidden' name='topic_id' value='{$topic_id}'>
	        <input type='submit' name='button' class='top20 button is-small' value='" . _('Delete Topic') . "'>
	    </form>";
    stderr(_('Error'), _('This is the first post in the topic, are you sure you want to delete the topic?') . $delete);
}
if ($sanity_check > 0) {
    if ($site_config['forum_config']['delete_for_real']) {
        $arr = $fluent->from('posts AS p')
                      ->select(null)
                      ->select('p.id')
                      ->select('t.forum_id')
                      ->leftJoin('topics AS t ON p.topic_id = t.id')
                      ->where('p.topic_id = ?', $topic_id)
                      ->orderBy('p.id DESC')
                      ->limit(1)
                      ->fetch();
        if (empty($arr['id'])) {
            $fluent->deleteFrom('topics')
                   ->where('id = ?', $topic_id)
                   ->execute();
        } else {
            $db->run(');
} else {
    global $page;

    $breadcrumbs = [
        "<a href='{$site_config['paths']['baseurl']}/forums.php'>" . _('Forums') . '</a>',
        "<a href='{$site_config['paths']['baseurl']}/forums.php?action=view_topic&topic_id={$topic_id}'>" . _('Topic') . '</a>',
        "<a href='{$site_config['paths']['baseurl']}/forums.php?action=view_topic&topic_id={$topic_id}&page={$page}#{$post_id}'>" . _('Post') . '</a>',
        "<a href='{$site_config['paths']['baseurl']}/forums.php?action=edit_post&post_id={$post_id}&topic_id={$topic_id}&page={$page}'>" . _('Delete Post') . '</a>',
    ];
    stderr(_('Sanity Check!'), _('Are you sure you want to delete this post? If so, click') . ' 
	<a class="is-link" href="' . $site_config['paths']['baseurl'] . '/forums.php?action=delete_post&amp;post_id=' . $post_id . '&amp;topic_id=' . $topic_id . '&amp;sanity_check=1">Here</a>.', '', '', $breadcrumbs);
}
