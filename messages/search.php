<?php

declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

use Pu239\Database;

$user = check_user_status();
global $container, $site_config;
$db = $container->get(Database::class);

$num_result = $and_member = '';
$keywords = isset($_POST['keywords']) ? htmlsafechars($_POST['keywords']) : '';
$member = isset($_POST['member']) ? htmlsafechars($_POST['member']) : '';
$all_boxes = isset($_POST['all_boxes']) ? (int) $_POST['all_boxes'] : '';
$sender_reciever = $mailbox >= 1 ? 'sender' : 'receiver';
$params = [':userid' => $user['id']];
$what_in_out = $mailbox >= 1 ? 'AND receiver = :userid' : 'AND sender = :userid';
$location = isset($_POST['all_boxes']) ? 'AND location != 0' : 'AND location = ' . $mailbox;
$limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 25;
$as_list_post = isset($_POST['as_list_post']) ? (int) $_POST['as_list_post'] : 2;
$desc_asc = isset($_POST['ASC']) == 1 ? 'ASC' : 'DESC';
$subject = isset($_POST['subject']) ? htmlsafechars($_POST['subject']) : '';
$text = isset($_POST['text']) ? htmlsafechars($_POST['text']) : '';
$member_sys = isset($_POST['system']) ? 'system' : '';
$possible_sort = [
    'added',
    'subject',
    'sender',
    'receiver',
    'relevance',
];
$box = isset($_POST['box']) ? (int) $_POST['box'] : 1;
$sort = (isset($_GET['sort']) ? htmlsafechars($_GET['sort']) : (isset($_POST['sort']) ? htmlsafechars($_POST['sort']) : 'relevance'));
if (!in_array($sort, $possible_sort)) {
    stderr(_('Error'), _('A ruffian that will swear, drink, dance, revel the night, rob, murder and commit the oldest of ins the newest kind of ways.'));
}

if ($member) {
    $res_username = $db->run('SELECT /* columns */ FROM users WHERE username = :member', [
        ':member' => $member,
    ]);
}
