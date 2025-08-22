<?php
require_once __DIR__ . '/runtime_safe.php';


declare(strict_types = 1);

$post_id = (isset($_GET['post_id']) ? (int) $_GET['post_id'] : (isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0));
$topic_id = (isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0));
$mode = (isset($_GET['mode']) ? htmlsafechars($_GET['mode']) : '');
if (!is_valid_id($post_id) || !is_valid_id($topic_id)) {
    stderr(_('Error'), _('Invalid ID.'));
}
//=== make sure it's their post or they are staff... this may change
$res_post = sql_query('SELECT p.user_id, p.staff_lock, u.id, u.class, u.status, t.locked, t.user_id AS owner_id, t.first_post, f.min_class_read, f.min_class_write, f.id AS forum_id FROM posts AS p LEFT JOIN users AS u ON p.user_id=u.id LEFT JOIN topics AS t ON t.id=p.topic_id LEFT JOIN forums AS f ON t.forum_id=f.id WHERE p.id=' . sqlesc($post_id)) or sqlerr(__FILE__, __LINE__);
$arr_post = mysqli_fetch_assoc($res_post);
global $CURUSER;

//=== if sysop let them lock the post
$can_lock = ($CURUSER['class'] >= UC_MAX);
//=== stop them, they shouldn't be here lol
//=== this is kinda long, but seems like a switch thing would be pointless, as you have to check them all...
if ($CURUSER['class'] < $arr_post['min_class_read'] || $CURUSER['class'] < $arr_post['min_class_write']) {
    stderr(_('Error'), _('Topic not found.'));
}
if ($CURUSER['forum_post'] === 'no' || $CURUSER['status'] !== 0) {
    stderr(_('Error'), _('Your posting rights have been suspended.'));
}
if (!$can_lock) {
    stderr(_('Error'), _('You dont have the credentials to lock.'));
}
if ($arr_post['locked'] === 'yes') {
    stderr(_('Error'), _('This topic is locked'));
}
if ($arr_post['staff_lock'] === 1 && $CURUSER['class'] < UC_MAX) {
    stderr(_('Error'), _('This post is already locked homes.'));
}
//=== ok... they made it this far, so let's lock the damned post!
if ($mode === 'lock') {
    sql_query("UPDATE posts SET status = 'postlocked', staff_lock = 1 WHERE id = " . sqlesc($post_id)) or sqlerr(__FILE__, __LINE__);
    //=== ok, all done here, send them back! \o/
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&post_id=' . $post_id . '&topic_id=' . $topic_id);
    app_halt();
}
if ($mode === 'unlock') {
    sql_query("UPDATE posts SET status = 'ok', staff_lock = 0 WHERE id = " . sqlesc($post_id)) or sqlerr(__FILE__, __LINE__);
    //=== ok, all done here, send them back! \o/
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&post_id=' . $post_id . '&topic_id=' . $topic_id);
    app_halt();
}
