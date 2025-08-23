<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Post;

flood_limit('forums');
$page = $colour = $arr_quote = '';
$topic_id = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0);
if (!is_valid_id($topic_id)) {
    stderr(_('Error'), _('Invalid ID'));
}
global $CURUSER, $site_config;

$res = sql_query('SELECT t.topic_name, t.topic_desc, t.locked, f.min_class_read, f.min_class_write, f.id AS real_forum_id, s.id AS subscribed_id FROM topics AS t LEFT JOIN forums AS f ON t.forum_id=f.id LEFT JOIN subscriptions AS s ON s.topic_id=t.id WHERE ' . ($CURUSER['class'] < UC_STAFF ? 't.status = \'ok\' AND' : ($CURUSER['class'] < $site_config['forum_config']['min_delete_view_class'] ? 't.status != \'deleted\'  AND' : '')) . ' t.id=' . sqlesc($topic_id)) or sqlerr(__FILE__, __LINE__);
$arr = mysqli_fetch_assoc($res);
if ($arr['locked'] === 'yes') {
    stderr(_('Error'), _('This topic is locked'));
}
if ($CURUSER['class'] < $arr['min_class_read'] || $CURUSER['class'] < $arr['min_class_write']) {
    stderr(_('Error'), _('Invalid ID'));
}
if ($CURUSER['forum_post'] === 'no' || $CURUSER['status'] !== 0) {
    stderr(_('Error'), _('Your posting rights have been suspended.'));
}
$quote = isset($_GET['quote_post']) ? (int) $_GET['quote_post'] : 0;
$key = isset($_GET['key']) ? (int) $_GET['key'] : 0;
$body = isset($_POST['body']) ? $_POST['body'] : '';
$post_title = strip_tags((isset($_POST['post_title']) ? $_POST['post_title'] : ''));
$icon = htmlsafechars(isset($_POST['icon']) ? $_POST['icon'] : '');
$bb_code = !isset($_POST['bb_code']) || $_POST['bb_code'] === 'yes' ? 'yes' : 'no';
$subscribe = ((isset($_POST['subscribe']) && $_POST['subscribe'] === 'yes') ? 'yes' : ((!isset($_POST['subscribe']) && $arr['subscribed_id'] > 0) ? 'yes' : 'no'));
$topic_name = format_comment($arr['topic_name']);
$topic_desc = format_comment($arr['topic_desc']);
$anonymous = isset($_POST['anonymous']) && $_POST['anonymous'] != '' ? '1' : '0';
if ($quote !== 0 && $body === '') {
    $res_quote = sql_query('SELECT p.body, p.staff_lock, p.anonymous, p.user_id, u.username FROM posts AS p LEFT JOIN users AS u ON p.user_id=u.id WHERE p.id=' . sqlesc($quote)) or sqlerr(__FILE__, __LINE__);
    $arr_quote = mysqli_fetch_array($res_quote);
    if ($arr_quote['anonymous'] === '1') {
        $quoted_member = ($arr_quote['username'] == '' ? '' . _('Lost member') . '' : '' . get_anonymous_name() . '');
    } else {
        $quoted_member = ($arr_quote['username'] == '' ? '' . _('Lost member') . '' : htmlsafechars($arr_quote['username']));
    }
    $body = '[quote=' . $quoted_member . ($quote > 0 ? ' | post=' . $quote : '') . ($key > 0 ? ' | key=' . $key : '') . ']' . htmlsafechars($arr_quote['body']) . '[/quote]';
    if ($arr_quote['staff_lock'] != 0) {
        stderr(_('Error'), '' . _('This post is staff locked, it cannot be modified, quoted or deleted.') . '');
    }
}
if (isset($_POST['button']) && $_POST['button'] === 'Post') {
    if ($body === '') {
        stderr(_('Error'), _('No body text.'));
    }
    $values = [
        'topic_id' => $topic_id,
        'user_id' => $CURUSER['id'],
        'added' => TIME_NOW,
        'body' => $body,
        'icon' => $icon,
        'post_title' => $post_title,
        'bbcode' => $bb_code,
        'anonymous' => $anonymous,
    ];

    $posts_class = $container->get(Post::class);
    $post_id = $posts_class->insert($values);
    clr_forums_cache((int) $arr['real_forum_id']);
    $cache->delete('forum_posts_' . $CURUSER['id']);
    sql_query('UPDATE topics SET last_post = ' . sqlesc($post_id) . ', post_count = post_count + 1 WHERE id=' . sqlesc($topic_id)) or sqlerr(__FILE__, __LINE__);
    sql_query('UPDATE `forums` SET post_count = post_count + 1 WHERE id =' . sqlesc($arr['real_forum_id'])) or sqlerr(__FILE__, __LINE__);
    sql_query('UPDATE usersachiev SET forumposts = forumposts + 1 WHERE userid=' . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
    if ($site_config['site']['autoshout_chat'] || $site_config['site']['autoshout_irc']) {
        $message = htmlsafechars($CURUSER['username']) . ' ' . _('replied to topic') . " [quote][url={$site_config['paths']['baseurl']}/forums.php?action=view_topic&topic_id=$topic_id&page=last#{$post_id}]" . $arr['topic_name'] . '[/url][/quote]';
        if (!in_array($arr['real_forum_id'], $site_config['staff_forums'])) {
            autoshout($message);
        }
    }
    if ($site_config['bonus']['on']) {
        sql_query('UPDATE users SET seedbonus = seedbonus + ' . sqlesc($site_config['bonus']['per_post']) . ' WHERE id = ' . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
        $update['seedbonus'] = ($CURUSER['seedbonus'] + $site_config['bonus']['per_post']);
        $cache->update_row('user_' . $CURUSER['id'], [
            'seedbonus' => $update['seedbonus'],
        ]);
    }
    if ($subscribe === 'yes' && $arr['subscribed_id'] < 1) {
        sql_query('INSERT INTO `subscriptions` (`user_id`, `topic_id`) VALUES (' . sqlesc($CURUSER['id']) . ', ' . sqlesc($topic_id) . ')') or sqlerr(__FILE__, __LINE__);
    } elseif ($subscribe === 'no' && $arr['subscribed_id'] > 0) {
        sql_query('DELETE FROM `subscriptions` WHERE `user_id`= ' . sqlesc($CURUSER['id']) . ' AND  `topic_id` = ' . sqlesc($topic_id)) or sqlerr(__FILE__, __LINE__);
    }
    $res_sub = sql_query('SELECT user_id FROM subscriptions WHERE topic_id =' . sqlesc($topic_id)) or sqlerr(__FILE__, __LINE__);
    while ($row = mysqli_fetch_assoc($res_sub)) {
        $res_yes = sql_query('SELECT subscription_pm, username FROM users WHERE id=' . sqlesc($row['user_id'])) or sqlerr(__FILE__, __LINE__);
        $arr_yes = mysqli_fetch_array($res_yes);
        $msg = '' . _('Hey there') . "!!! \n " . _('a thread you subscribed to') . ': ' . htmlsafechars($arr['topic_name']) . ' ' . _('has had a new post') . "!\n click [url={$site_config['paths']['baseurl']}/forums.php?action=view_topic&topic_id={$topic_id}&page=last#{$post_id}][b]" . _('Here') . '[/b][/url] ' . _('to read it') . "!\n\n" . _('To view your subscriptions or un-subscribe, click') . " [url={$site_config['paths']['baseurl']}/forums.php?action=subscriptions][b]" . _('Here') . "[/b][/url].\n\nCheers.";
        if ($arr_yes['subscription_pm'] === 'yes' && $row['user_id'] != $CURUSER['id']) {
            sql_query("INSERT INTO messages (sender, subject, receiver, added, msg) VALUES(2, '" . _('New post in subscribed thread') . "!', " . sqlesc($row['user_id']) . ", '" . TIME_NOW . "', " . sqlesc($msg) . ')') or sqlerr(__FILE__, __LINE__);
        }
    }

    $extension_error = $size_error = 0;
    if (!empty($_FILES)) {
        require_once FORUM_DIR . 'attachment.php';
        $uploaded = upload_attachments($post_id);
        $extension_error = $uploaded[0];
        $size_error = $uploaded[1];
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id . ($extension_error === '' ? '' : '&ee=' . $extension_error) . ($size_error === '' ? '' : '&se=' . $size_error) . '&page=last#' . $post_id);
    die();
}

$HTMLOUT .= '
    <h1 class="has-text-centered">' . _('Reply in topic') . ' "<a class="is-link" href="' . $site_config['paths']['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '">' . format_comment($arr['topic_name']) . '</a>"</h1>
    <form method="post" action="' . $site_config['paths']['baseurl'] . '/forums.php?action=post_reply&amp;topic_id=' . $topic_id . '" enctype="multipart/form-data" accept-charset="utf-8">';

require_once FORUM_DIR . 'editor.php';

$HTMLOUT .= '
        <div class="has-text-centered margin20">
            <input type="submit" name="button" class="button is-small" value="' . _('Post') . '">
        </div>
    </form>';

require_once FORUM_DIR . 'last_ten.php';
