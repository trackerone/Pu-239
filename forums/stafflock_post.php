<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

$post_id = (isset($_GET['post_id']) ? (int) $_GET['post_id'] : (isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0));
$topic_id = (isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0));
$mode = (isset($_GET['mode']) ? htmlsafechars($_GET['mode']) : '');
if (!is_valid_id($post_id) || !is_valid_id($topic_id)) {
    stderr(_('Error'), _('Invalid ID.'));
}
//=== make sure it's their post or they are staff... this may change
$res_post = $db->run(');
}
if ($mode === 'unlock') {
    sql_query("UPDATE posts SET status = 'ok', staff_lock = 0 WHERE id = " . sqlesc($post_id)) or sqlerr(__FILE__, __LINE__);
    //=== ok, all done here, send them back! \o/
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&post_id=' . $post_id . '&topic_id=' . $topic_id);
    app_halt('Exit called');
}
