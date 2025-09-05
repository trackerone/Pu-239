<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;
use Pu239\Session;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_comments.php';
$user = check_user_status();
global $container, $site_config;

$stdhead = [
    'css' => [
        get_file_name('sceditor_css'),
    ],
];
$stdfoot = [
    'js' => [
        get_file_name('sceditor_js'),
    ],
];
$HTMLOUT = '';
$action = isset($_GET['action']) ? htmlsafechars($_GET['action']) : '';

$fluent = $container->get(Database::class);
$users_class = $container->get(User::class);
if ($action === 'add') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userid = (int) $_POST['userid'];
        if (!is_valid_id($userid)) {
            stderr(_('Error'), _('Invalid ID'));
        }
        $arr = $users_class->getUserFromId($userid);
        if (!$arr) {
            stderr(_('Error'), 'No user with that ID.');
        }
        $body = isset($_POST['body']) ? htmlsafechars($_POST['body']) : '';
        if (!$body) {
            stderr(_('Error'), 'Comment body cannot be empty!');
        }
        $values = [
            'user' => $user['id'],
            'userid' => $userid,
            'added' => TIME_NOW,
            'text' => $body,
            'ori_text' => $body,
        ];
        $newid = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
        $count = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    if ($allrows) {
        $HTMLOUT .= '
            <h2>Most recent comments, in reverse order</h2>' . commenttable($allrows, 'userdetails');
    }
    $title = _('Add Comment');
    $breadcrumbs = [
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
} elseif ($action === 'edit') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $commentid = (int) $_POST['cid'];
    } else {
        $commentid = (int) $_GET['cid'];
    }
    if (!is_valid_id($commentid)) {
        stderr(_('Error'), _('Invalid ID'));
    }
    $arr = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
    if (!$arr) {
        stderr(_('Error'), _('Invalid ID'));
    }
    if ($arr['user'] != $user['id'] && !has_access($user['class'], UC_STAFF, '')) {
        stderr(_('Error'), 'Permission denied.');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = htmlsafechars($_POST['body']);
        $returnto = htmlsafechars($_POST['returnto']);
        if ($body == '') {
            stderr(_('Error'), 'Comment body cannot be empty!');
        }
        $set = [
            'text' => $body,
            'editedat' => TIME_NOW,
            'editedby' => $user['id'],
        ];
        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
        if ($returnto) {
            header("Location: $returnto");
        } else {
            header("Location: {$_SERVER['PHP_SELF']}?id={$userid}#comments");
        }
        app_halt('Exit called');
    }
    $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $HTMLOUT .= '
    <h1 class="has-text-centered">Edit comment for ' . format_username((int) $arr['userid']) . "</h1>
    <form method='post' action='usercomment.php?action=edit&amp;cid={$commentid}' enctype='multipart/form-data' accept-charset='utf-8'>
    <input type='hidden' name='returnto' value='{$referer}'>
    <input type='hidden' name='cid' value='" . (int) $commentid . "'>
    <textarea name='body' rows='10' class='w-100'>" . htmlsafechars($arr['text']) . "</textarea>
    <div class='has-text-centered margin20'>
        <input type='submit' class='button is-small' value='Do it!'>
    </div></form>";
    $title = _('Edit Comment');
    $breadcrumbs = [
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
} elseif ($action === 'delete') {
    $commentid = (int) $_GET['cid'];
    if (!is_valid_id($commentid)) {
        stderr(_('Error'), _('Invalid ID'));
    }
    $sure = isset($_GET['sure']) ? (int) $_GET['sure'] : false;
    if (!$sure) {
        $referer = $_SERVER['HTTP_REFERER'];
        stderr('Delete comment', "You are about to delete a comment. Click\n" . "<a href='usercomment.php?action=delete&amp;cid=$commentid&amp;sure=1" . ($referer ? '&amp;returnto=' . urlencode($referer) : '') . "'><span class='has-text-success'>here</span></a> if you are sure.");
    }
    $arr = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    if ($arr) {
        $userid = (int) $arr['userid'];
    }
    if ($arr['id'] != $user['id'] && has_access($user['class'], UC_STAFF, 'coder')) {
        stderr(_('Error'), 'Permission denied.');
    }
    $deleted = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    if ($userid && $deleted) {
        $count = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

    if (!$arr) {
        stderr(_('Error'), _('Invalid ID'));
    }
    $HTMLOUT = "
        <h1 class='has-text-centered'>" . _('Original contents of comment ') . "#$commentid</h1>" . main_div("<div class='margin10 bg-02 round10 column'>" . format_comment(htmlsafechars($arr['ori_text'])) . '</div>');

    $returnto = (isset($_SERVER['HTTP_REFERER']) ? htmlsafechars($_SERVER['HTTP_REFERER']) : '');
    if ($returnto) {
        $HTMLOUT .= "
            <div class='has-text-centered margin20'>
                <a href='$returnto#comments' class='button is-small has-text-black'>back</a>
            </div>  ";
    }
    $title = _('Original Comment');
    $breadcrumbs = [
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
} else {
    stderr(_('Error'), 'Unknown action');
}
app_halt('Exit called');
