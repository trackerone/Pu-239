<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

use Pu239\Post;

flood_limit('forums');
$page = $colour = $arr_quote = '';
$topic_id = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0);
if (!is_valid_id($topic_id)) {
    stderr(_('Error'), _('Invalid ID'));
}
global $CURUSER, $site_config;

$rows = $db->fetchAll('SELECT t.topic_name, t.topic_desc, t.locked, f.min_class_read, f.min_class_write, f.id AS real_forum_id, s.id AS subscribed_id FROM topics AS t LEFT JOIN forums AS f ON t.forum_id=f.id LEFT JOIN subscriptions AS s ON s.topic_id=t.id WHERE ' . ($CURUSER['class'] < UC_STAFF ? 't.status = \'ok\' AND' : ($CURUSER['class'] < $site_config['forum_config']['min_delete_view_class'] ? 't.status != \'deleted\'  AND' : '')) . ' t.id=' . sqlesc($topic_id)) or sqlerr(__FILE__, __LINE__);
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
    $res_quote = $db->run(');
    } elseif ($subscribe === 'no' && $arr['subscribed_id'] > 0) {
        $db->run(');
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
    app_halt('Exit called');
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
