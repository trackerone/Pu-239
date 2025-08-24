<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

$post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : (isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0);
$topic_id = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0);
$page = isset($_GET['page']) ? (int) $_GET['page'] : (isset($_POST['page']) ? (int) $_POST['page'] : 0);
if (!is_valid_id($post_id) || !is_valid_id($topic_id)) {
    stderr(_('Error'), _('Bad ID.'));
}
$res_post = $db->run(');
if (!has_access($CURUSER['class'], (int) $arr_post['min_class_read'], '') || !has_access($CURUSER['class'], (int) $arr_post['min_class_write'], '')) {
    stderr(_('Error'), _('Topic not found.'));
}
if ($CURUSER['forum_post'] === 'no' || $CURUSER['status'] !== 0) {
    stderr(_('Error'), _('Your posting rights have been suspended.'));
}
if (!$can_edit) {
    stderr(_('Error'), '' . _('This is not your post to edit.') . '');
}
if ($arr_post['locked'] === 'yes') {
    stderr(_('Error'), _('This topic is locked'));
}
if ($arr_post['staff_lock'] === 1) {
    stderr(_('Error'), _('This post is staff locked homey, no correcting for you.'));
}
$edited_by = $CURUSER['id'];
$body = isset($_POST['body']) ? $_POST['body'] : $arr_post['body'];
if ($can_edit) {
    $topic_name = isset($_POST['topic_name']) ? htmlsafechars($_POST['topic_name']) : htmlsafechars($arr_post['topic_name']);
    $topic_desc = isset($_POST['topic_desc']) ? htmlsafechars($_POST['topic_desc']) : htmlsafechars($arr_post['topic_desc']);
}
$post_title = isset($_POST['post_title']) ? htmlsafechars($_POST['post_title']) : htmlsafechars($arr_post['post_title']);
$icon = isset($_POST['icon']) ? htmlsafechars($_POST['icon']) : htmlsafechars($arr_post['icon']);
$show_bbcode = isset($_POST['bb_code']) ? $_POST['bb_code'] : $arr_post['bbcode'];
$bb_code = $show_bbcode;
$edit_reason = isset($_POST['edit_reason']) ? htmlsafechars($_POST['edit_reason']) : '';
$show_edited_by = ((isset($_POST['show_edited_by']) && $_POST['show_edited_by'] === 'no' && $CURUSER['class'] >= $site_config['allowed']['show_edited_by'] && $CURUSER['id'] == $arr_post['id']) ? 'no' : 'yes');
if (isset($_POST['button']) && $_POST['button'] === 'Edit') {
    if (empty($body)) {
        stderr(_('Error'), _('Body text can not be empty.'));
    }
    $changed = '<span style="color:red;">' . _('Changed') . '</span>';
    $not_changed = '<span style="color:green;">' . _('Not changed') . '</span>';
    $post_history = main_div("
        <div class='w-100 padding10'>
            <div class='columns is-marginless'>
                <div class='column is-one-quarter round10 bg-02 padding20'>
                    <div class='columns is-marginless'>
                        <div class='column is-paddingless is-one-third'>Edited:</div>
                         <div class='column is-paddingless'>" . get_date((int) $arr_post['edit_date'], 'LONG', 1, 0) . "</div>
                    </div>
                    <div class='columns is-marginless'>
                        <div class='column is-paddingless is-one-third'>Desc:</div>
                         <div class='column is-paddingless'>" . (!empty($arr_post['topic_desc']) ? 'yes' : 'none') . "</div>
                    </div>
                    <div class='columns is-marginless'>
                        <div class='column is-paddingless is-one-third'>" . _('Title') . ":</div>
                         <div class='column is-paddingless'>" . (!empty($arr_post['post_title']) ? 'yes' : 'none') . "</div>
                    </div>
                    <div class='columns is-marginless'>
                        <div class='column is-paddingless is-one-third'>" . _('Icon') . ":</div>
                         <div class='column is-paddingless'>" . (!empty($arr_post['icon']) ? 'yes' : 'none') . "</div>
                    </div>
                    <div class='columns is-marginless'>
                        <div class='column is-paddingless is-one-third'>" . _('BB code') . ":</div>
                         <div class='column is-paddingless'>" . ($show_bbcode === 'yes' ? 'on' : 'off') . '</div>' . ($can_edit ? "
                    </div>
                    <div class='columns is-marginless'>
                        <div class='column is-paddingless is-one-third'>Topic Name:</div>
                         <div class='column is-paddingless'>" . ((isset($_POST['topic_name']) && $_POST['topic_name'] !== $arr_post['topic_name']) ? $changed : $not_changed) . "</div>
                    </div>
                    <div class='columns is-marginless'>
                        <div class='column is-paddingless is-one-third'>Desc:</div>
                         <div class='column is-paddingless'>" . ((isset($_POST['topic_desc']) && $_POST['topic_desc'] !== $arr_post['topic_desc']) ? $changed : $not_changed) . '</div>' : '') . "
                    </div>
                    <div class='columns is-marginless'>
                        <div class='column is-paddingless is-one-third'>" . _('Title') . ":</div>
                         <div class='column is-paddingless'>" . ((isset($_POST['post_title']) && $_POST['post_title'] !== $arr_post['post_title']) ? $changed : $not_changed) . "</div>
                    </div>
                    <div class='columns is-marginless'>
                        <div class='column is-paddingless is-one-third'>" . _('Icon') . ":</div>
                         <div class='column is-paddingless'>" . ((isset($_POST['icon']) && $_POST['icon'] !== $arr_post['icon']) ? $changed : $not_changed) . "</div>
                    </div>
                    <div class='columns is-marginless'>
                        <div class='column is-paddingless is-one-third'>" . _('BB code') . ":</div>
                         <div class='column is-paddingless'>" . (($show_bbcode !== $arr_post['bbcode']) ? $changed : $not_changed) . "</div>
                    </div>
                    <div class='columns is-marginless'>
                        <div class='column is-paddingless is-one-third'>" . _('Body') . ":</div>
                         <div class='column is-paddingless'>" . ((isset($_POST['body']) && $_POST['body'] !== $arr_post['body']) ? $changed : $not_changed) . "</div>
                    </div>
                </div>
                <div class='column round10 bg-02 left10'>" . format_comment($arr_post['body']) . '</div>
            </div>
        </div>', (!empty($arr_post['post_history']) ? 'bottom20' : '')) . $arr_post['post_history'];
    $db->run('UPDATE posts SET body = ' . sqlesc(htmlsafechars($body)) . ', icon = ' . sqlesc($icon) . ', post_title = ' . sqlesc($post_title) . ', bbcode = ' . sqlesc($show_bbcode) . ', edit_reason = ' . sqlesc($edit_reason) . ', edited_by = ' . sqlesc($edited_by) . ', edit_date = ' . sqlesc(TIME_NOW) . ', post_history = ' . sqlesc($post_history) . ' WHERE id = :id', [':id' => $post_id]) or sqlerr(__FILE__, __LINE__);
    clr_forums_cache($post_id);
    $cache->delete('forum_posts_' . $CURUSER['id']);
    if ($can_edit) {
        $db->run('UPDATE topics SET topic_name = ' . sqlesc($topic_name) . ', topic_desc = ' . sqlesc($topic_desc) . ' WHERE id = :id', [':id' => $topic_id]) or sqlerr(__FILE__, __LINE__);
    }
    $extension_error = $size_error = 0;
    if (!empty($_FILES)) {
        require_once FORUM_DIR . 'attachment.php';
        $uploaded = upload_attachments($post_id);
        $extension_error = $uploaded[0];
        $size_error = $uploaded[1];
    }
    if (!empty($_POST['attachment_to_delete']) && is_array($_POST['attachment_to_delete'])) {
        foreach ($_POST['attachment_to_delete'] as $var) {
            $attachment_to_delete = intval($var);
            $attachments_res = $db->run(');
}
$HTMLOUT .= '
	<h1 class="has-text-centered">' . _('Edit post by') . ': ' . format_username((int) $arr_post['user_id']) . ' ' . _('In topic') . ' 
	"<a class="is-link" href="' . $site_config['paths']['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '">' . htmlsafechars($arr_post['topic_name']) . '</a>"</h1>
	<form method="post" action="' . $site_config['paths']['baseurl'] . '/forums.php?action=edit_post&amp;topic_id=' . $topic_id . '&amp;post_id=' . $post_id . '&amp;page=' . $page . '" enctype="multipart/form-data" accept-charset="utf-8">';
require_once FORUM_DIR . 'editor.php';

$HTMLOUT .= '
	<div class="has-text-centered">
	<input type="submit" name="button" class="button is-small margin20" value="Edit">
    </div>
    </form>';

require_once FORUM_DIR . 'last_ten.php';
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/forums.php'>" . _('Forums') . '</a>',
    "<a href='{$site_config['paths']['baseurl']}/forums.php?action=view_topic&topic_id={$topic_id}'>{$topic_name}</a>",
    "<a href='{$site_config['paths']['baseurl']}/forums.php?action=view_topic&topic_id={$topic_id}&page={$page}#{$post_id}'>" . _('Post') . '</a>',
    "<a href='{$site_config['paths']['baseurl']}/forums.php?action=edit_post&post_id={$post_id}&topic_id={$topic_id}&page={$page}'>" . _('Edit Post') . '</a>',
];
