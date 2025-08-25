<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Comment;
use Pu239\Database;
use Pu239\Message;
use Pu239\Session;
use Pu239\Torrent;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once INCL_DIR . 'function_comments.php';
$user = check_user_status();
global $container;
$db = $container->get(Database::class);, $site_config;

$comments = $container->get(Comment::class);
$user_class = $container->get(User::class);
$torrents = $container->get(Torrent::class);
$action = !empty($_GET['action']) ? htmlsafechars($_GET['action']) : (!empty($_POST['action']) ? htmlsafechars($_POST['action']) : 0);
$stdhead = [
    'css' => [
        get_file_name('sceditor_css'),
    ],
];
$stdfoot = [
    'js' => [
        get_file_name('upload_js'),
        get_file_name('sceditor_js'),
    ],
];

$locale = 'torrent';
$locale_link = 'details';
$extra_link = '';
$sql_1 = 'name, owner, comments, anonymous FROM torrents';
$name = 'name';
$table_type = $locale . 's';
$_GET['type'] = isset($_GET['type']) ? htmlsafechars($_GET['type']) : (isset($_POST['locale']) ? htmlsafechars($_POST['locale']) : '');
if (isset($_GET['type'])) {
    $type_options = [
        'torrent' => 'details',
        'request' => 'viewrequests',
    ];
    if (isset($type_options[$_GET['type']])) {
        $locale_link = $type_options[$_GET['type']];
        $locale = $_GET['type'];
    }
    switch ($_GET['type']) {
        case 'request':
            $sql_1 = 'request FROM requests';
            $name = 'request';
            $extra_link = '&req_details';
            $table_type = $locale . 's';
            break;

        default:
            $sql_1 = 'name, owner, comments, anonymous FROM torrents';
            $name = 'name';
            $table_type = $locale . 's';
            break;
    }
}

$cache = $container->get(Cache::class);
$messages_class = $container->get(Message::class);
$session = $container->get(Session::class);
if ($action === 'add') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = isset($_POST['tid']) ? (int) $_POST['tid'] : 0;
        if (!is_valid_id($id)) {
            stderr(_('Error'), _('Invalid ID'));
        }
        $rows = $db->fetchAll("SELECT $sql_1 WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
        $arr = mysqli_fetch_array($res);
        if (!$arr) {
            stderr(_('Error'), _fe('No {0} with that ID.', $locale));
        }
        $body = isset($_POST['body']) ? trim($_POST['body']) : '';
        if (!$body) {
            stderr(_('Error'), _('Comment body cannot be empty!'));
        }
        $owner = isset($arr['owner']) ? (int) $arr['owner'] : 0;
        $arr['anonymous'] = isset($arr['anonymous']) && $arr['anonymous'] === '1' ? '1' : '0';
        $arr['comments'] = isset($arr['comments']) ? $arr['comments'] : 0;
        if ($user['id'] == $owner && $arr['anonymous'] === '1' || (isset($_POST['anonymous']) && $_POST['anonymous'] === '1')) {
            $anon = 1;
        } else {
            $anon = 0;
        }
        $values = [
            'user' => $user['id'],
            'torrent' => $id,
            'added' => TIME_NOW,
            'text' => $body,
            'ori_text' => $body,
            'anonymous' => $anon,
        ];
        $fluent = $db; // alias
$fluent = $container->get(Database::class);
        $newid = $fluent->insertInto('comments')
                        ->values($values)
                        ->execute();

        $db->run(");
        app_halt('Exit called');
    }
    $id = isset($_GET['tid']) ? (int) $_GET['tid'] : 0;
    if (!is_valid_id($id)) {
        stderr(_('Error'), _('Invalid ID'));
    }
    $rows = $db->fetchAll("SELECT $sql_1 WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
    $arr = mysqli_fetch_assoc($res);
    if (!$arr) {
        stderr(_('Error'), _fe('No {0} with that ID.', $locale));
    }
    $HTMLOUT = '';
    $body = htmlsafechars((isset($_POST['body']) ? $_POST['body'] : ''));
    $HTMLOUT .= "<h1 class='has-text-centered'>" . _fe('Add a comment to {0}', format_comment($arr[$name])) . "</h1>
      <br><form name='compose' method='post' action='{$_SERVER['PHP_SELF']}?action=add' enctype='multipart/form-data' accept-charset='utf-8'>
      <input type='hidden' name='tid' value='{$id}'/>
      <input type='hidden' name='locale' value='$name'>";
    $HTMLOUT .= BBcode($body);
    $HTMLOUT .= "
        <div class='has-text-centered margin20'>
            <label for='anonymous'>" . _('Check this to post anonymously') . "</label>
            <input id='anonymous' type='checkbox' name='anonymous' value='1'><br>
            <input type='submit' class='button is-small top20' value='" . _('Post Comment') . "'>
        </div>
    </form>";
    $sql = "SELECT c.id, c.text, c.added, c.$locale, c.anonymous, c.editedby, c.editedat, c.user, u.id as user, u.title, u.avatar, u.offensive_avatar, u.class, u.reputation, u.mood, u.donor, u.warned
                        FROM comments AS c
                        LEFT JOIN users AS u ON c.user = u.id
                        WHERE $locale = " . sqlesc($id) . '
                        ORDER BY c.id DESC
                        LIMIT 5';
    $res = sql_query($sql) or sqlerr(__FILE__, __LINE__);
    $allrows = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $allrows[] = $row;
    }
    if (!empty($allrows) && count($allrows)) {
        require_once INCL_DIR . 'function_html.php';
        require_once INCL_DIR . 'function_bbcode.php';
        require_once INCL_DIR . 'function_users.php';
        require_once INCL_DIR . 'function_comments.php';
        $HTMLOUT = wrapper($HTMLOUT);
        $HTMLOUT .= wrapper("<h2 class='has-text-centered'>" . _('Most recent comments, in reverse order') . '</h2>' . commenttable($allrows, $locale));
    }
    $title = _('Add Comment');
    $breadcrumbs = [
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
} elseif ($action === 'edit') {
    $commentid = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
    if (!is_valid_id($commentid)) {
        stderr(_('Error'), _('Invalid ID'));
    }
    $res = $db->run(");
        app_halt('Exit called');
    }
    $HTMLOUT = '';
    $HTMLOUT .= "<h1 class='has-text-centered'>" . _fe("Edit comment to '{0}'", format_comment($arr[$name])) . "</h1>
      <form method='post' action='{$_SERVER['PHP_SELF']}?action=edit&amp;cid=$commentid' enctype='multipart/form-data' accept-charset='utf-8'>
      <input type='hidden' name='locale' value='$name'>
       <input type='hidden' name='tid' value='" . (int) $arr['tid'] . "'>
      <input type='hidden' name='cid' value='$commentid'>";
    $HTMLOUT .= BBcode($arr['text']);
    $HTMLOUT .= '
      <br>' . ($user['class'] >= UC_STAFF ? '<input type="checkbox" value="lasteditedby" checked name="lasteditedby" id="lasteditedby"> Show Last Edited By<br><br>' : '') . '
        <div class="has-text-centered margin20">
            <input type="submit" class="button is-small" value="' . _('Do it!') . '">
        </div>
    </form>';
    $title = _('Edit Comment');
    $breadcrumbs = [
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
} elseif ($action === 'delete') {
    if ($user['class'] < UC_STAFF) {
        stderr(_('Error'), _('Permission denied.'));
    }
    $commentid = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
    $tid = isset($_GET['tid']) ? (int) $_GET['tid'] : 0;
    if (!is_valid_id($commentid)) {
        stderr(_('Error'), _('Invalid ID'));
    }
    $sure = isset($_GET['sure']) ? (int) $_GET['sure'] : false;
    if (!$sure) {
        stderr(_('Delete comment'), _('You are about to delete a comment. Click') . " <a href='{$_SERVER['PHP_SELF']}?action=delete&amp;cid=$commentid&amp;tid=$tid&amp;sure=1" . ($locale === 'request' ? '&amp;type=request' : '') . "'>
          <span class='has-text-success'>" . _('here') . '</span></a> ' . _('if you are sure.'));
    }
    $rows = $db->fetchAll("SELECT $locale FROM comments WHERE id = " . sqlesc($commentid)) or sqlerr(__FILE__, __LINE__);
    $arr = mysqli_fetch_assoc($res);
    $id = 0;
    if ($arr) {
        $id = (int) $arr[$locale];
    }
    $deleted = $comments->delete($commentid);
    if ($id && $deleted) {
        $db->run(");
    app_halt('Exit called');
} elseif ($action === 'vieworiginal') {
    if ($user['class'] < UC_STAFF) {
        stderr(_('Error'), _('Permission denied.'));
    }
    $commentid = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
    if (!is_valid_id($commentid)) {
        stderr(_('Error'), _('Invalid ID'));
    }
    $rows = $db->fetchAll("SELECT c.*, t.$name FROM comments AS c LEFT JOIN $table_type AS t ON c.$locale = t.id WHERE c.id=" . sqlesc($commentid)) or sqlerr(__FILE__, __LINE__);
    $arr = mysqli_fetch_assoc($res);
    if (!$arr) {
        stderr(_('Error'), _('Invalid ID') . " $commentid.");
    }
    $HTMLOUT = "
        <h1 class='has-text-centered'>" . _('Original contents of comment ') . "#$commentid</h1>" . main_div("<div class='margin10 bg-02 round10 column'>" . format_comment(htmlsafechars($arr['ori_text'])) . '</div>');

    $returnto = isset($_SERVER['HTTP_REFERER']) ? htmlsafechars($_SERVER['HTTP_REFERER']) : '';
    if ($returnto) {
        preg_match('/viewcomm=(\d+)/', $returnto, $match);
        $hashtag = !empty($match[1]) ? '#comm' . $match[1] : '';
        $HTMLOUT .= "
            <div class='has-text-centered margin20'>
                <a href='$returnto{$hashtag}' class='button is-small has-text-black'>back</a>
            </div>  ";
    }
    $title = _('Origianl Comment');
    $breadcrumbs = [
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
} else {
    stderr(_('Error'), _('Unknown action'));
}
