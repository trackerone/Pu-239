<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

$topic_id = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0);
global $site_config, $CURUSER;

if ($topic_id > 0) {
    $db->run(');
    app_halt('Exit called');
}
if (isset($_POST['remove'])) {
    $_POST['remove'] = isset($_POST['remove']) ? $_POST['remove'] : [];
    $post_delete = [];
    foreach ($_POST['remove'] as $somevar) {
        $post_delete[] = intval($somevar);
    }
    $post_delete = array_unique($post_delete);
    $delete_count = count($post_delete);
    if ($delete_count > 0) {
        $db->run(');
app_halt('Exit called');
