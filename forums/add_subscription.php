<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

global $CURUSER, $site_config;

$topic_id = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : (isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0);
$forum_id = isset($_GET['forum_id']) ? (int) $_GET['forum_id'] : (isset($_POST['forum_id']) ? (int) $_POST['forum_id'] : 0);
$norty_res = $db->run(');
header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_topic&topic_id=' . $topic_id . '&s=1');
app_halt('Exit called');
