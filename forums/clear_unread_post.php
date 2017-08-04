<?php
if (!defined('BUNNY_FORUMS')) {
    $HTMLOUT = '';
    $HTMLOUT .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
        <head>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
        <title>ERROR</title>
        </head><body>
        <h1 style="text-align:center;">ERROR</h1>
        <p style="text-align:center;">How did you get here? silly rabbit Trix are for kids!.</p>
        </body></html>';
    echo $HTMLOUT;
    exit();
}
global $lang;

$topic_id = (isset($_GET['topic_id']) ? intval($_GET['topic_id']) : (isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0));
$last_post = (isset($_GET['last_post']) ? intval($_GET['last_post']) : (isset($_POST['last_post']) ? intval($_POST['last_post']) : 0));
$check_it = sql_query('SELECT id, last_post_read FROM read_posts WHERE user_id=' . sqlesc($CURUSER['id']) . ' and topic_id=' . sqlesc($topic_id));
$check_it_arr = mysqli_fetch_assoc($check_it);
//===  update read posts
if ($check_it_arr['last_post_read'] > 0) {
    sql_query('UPDATE read_posts SET last_post_read = ' . sqlesc($last_post) . ' WHERE topic_id = ' . sqlesc($topic_id) . ' AND user_id = ' . sqlesc($CURUSER['id']));
    $mc1->delete_value('last_read_post_' . $topic_id . '_' . $CURUSER['id']);
    $mc1->delete_value('sv_last_read_post_' . $topic_id . '_' . $CURUSER['id']);
} else {
    sql_query('INSERT INTO read_posts (`user_id` ,`topic_id` ,`last_post_read`) VALUES (' . sqlesc($CURUSER['id']) . ', ' . sqlesc($topic_id) . ', ' . sqlesc($last_post) . ')');
    $mc1->delete_value('last_read_post_' . $topic_id . '_' . $CURUSER['id']);
    $mc1->delete_value('sv_last_read_post_' . $topic_id . '_' . $CURUSER['id']);
}
//=== ok, all done here, send them back! \o/
header('Location: ' . $INSTALLER09['baseurl'] . '/forums.php?action=view_unread_posts');
die();
