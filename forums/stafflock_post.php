<?php

declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

use Pu239\Database;

$db = $container->get(Database::class);

$post_id = (isset($_GET['post_id']) ? (int) $_GET['post_id'] : (isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0));
$topic_id = (isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0));
$mode = (isset($_GET['mode']) ? htmlsafechars($_GET['mode']) : '');
if (!is_valid_id($post_id) || !is_valid_id($topic_id)) {
    stderr(_('Error'), _('Invalid ID.'));
}
//=== make sure it's their post or they are staff... this may change
$res_post = $db->run('SELECT user_id FROM posts WHERE id = :id', [':id' => $post_id])->fetch();
if ($mode === 'unlock') {
    $db->run('UPDATE posts SET status = ?, staff_lock = 0 WHERE id = ?', ['ok', $post_id]);
    //=== ok, all done here, send them back! \o/
    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&post_id=' . $post_id . '&topic_id=' . $topic_id);
    app_halt('Exit called');
}
