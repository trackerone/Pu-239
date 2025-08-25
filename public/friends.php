<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use Pu239\Session;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
$curuser = check_user_status();
global $container;
$db = $container->get(Database::class);, $site_config;

$userid = isset($_GET['id']) ? (int) $_GET['id'] : $curuser['id'];
$action = isset($_GET['action']) ? htmlsafechars($_GET['action']) : '';
if (!is_valid_id($userid)) {
    stderr(_('Error'), _('Invalid ID'));
}
if ($userid != $curuser['id']) {
    stderr(_('Error'), _('Access denied.'));
}
$dt = TIME_NOW;
$fluent = $db; // alias
$fluent = $container->get(Database::class);
$session = $container->get(Session::class);
$messages_class = $container->get(Message::class);
$cache = $container->get(Cache::class);
if ($action === 'add') {
    if (!isset($_GET['targetid'])) {
        stderr(_('Error'), _('Invalid ID'));
    }
    $targetid = (int) $_GET['targetid'];
    $type = $_GET['type'];
    if (!is_valid_id($targetid)) {
        stderr(_('Error'), _('Invalid ID'));
    }
    if ($curuser['id'] == $targetid) {
        stderr(_('Error'), _("You can't add yourself."));
    }
    if ($type === 'friend') {
        $table_is = $frag = 'friends';
        $field_is = 'friendid';
        $confirmed = 'confirmed';
    } elseif ($type === 'block') {
        $table_is = $frag = 'blocks';
        $field_is = 'blockid';
    } else {
        stderr(_('Error'), _('Unknown type.'));
    }
    if ($type === 'friend') {
        $r = $db->run(");
        stderr(_('Request Added!'), _fe('The user will be informed of your Friend Request, you will be informed via PM upon confirmation.<br><br>{0}Go to your Friends List{1}', "<a href='{$site_config['paths']['baseurl']}/friends.php?id=$userid#$frag'><b>", '</b></a>'));
        app_halt('Exit called');
    }
    if ($type === 'block') {
        $r = $db->run(");
        app_halt('Exit called');
    }
}
//== action == confirm
if ($action === 'confirm') {
    $targetid = (int) $_GET['targetid'];
    $sure = isset($_GET['sure']) ? (int) $_GET['sure'] : false;
    if (isset($_GET['type'])) {
        $type = $_GET['type'] === 'friend' ? 'friend' : 'block';
    } else {
        stderr(_('Error'), _('Invalid type.'));
    }
    if (!is_valid_id($targetid)) {
        stderr(_('Error'), _('Invalid ID'));
    }
    $hash = md5('c@@me' . $curuser['id'] . $targetid . $type . 'confirm' . 'sa7t');
    if (!$sure) {
        stderr(_('Confirm Friend'), _fe('Do you really want to confirm this person? Click {0}here{1} if you are sure.', "<a href='{$site_config['paths']['baseurl']}/friends.php?id=$userid&amp;action=confirm&amp;type=$type&amp;targetid=$targetid&amp;sure=1&amp;h=$hash'><b>", '</b></a>'));
    }
    if ($_GET['h'] != $hash) {
        stderr(_('Error'), _('Invalid data.'));
    }
    if ($type === 'friend') {
        $db->run(');
        $msg = _fe('{0} has just confirmed your Friendship Request. See your Friends {1}here{2}', "[url={$site_config['paths']['baseurl']}/userdetails.php?id=$userid][b]{$curuser['username']}[/b][/url]", "[url={$site_config['paths']['baseurl']}/friends.php][b]", '[/b][/url]');
        $msgs_buffer[] = [
            'receiver' => $targetid,
            'added' => $dt,
            'msg' => $msg,
            'subject' => $subject,
        ];
        $messages_class->insert($msgs_buffer);
        $frag = 'friends';
        $session->set('is-success', _('Friend was added successfully.'));
    }
} elseif ($action === 'delpending') {
    $targetid = (int) $_GET['targetid'];
    $sure = isset($_GET['sure']) ? (int) $_GET['sure'] : false;
    $type = htmlsafechars($_GET['type']);
    if (!is_valid_id($targetid)) {
        stderr(_('Error'), _('Invalid ID'));
    }
    $hash = md5('c@@me' . $curuser['id'] . $targetid . $type . 'confirm' . 'sa7t');
    if (!$sure) {
        stderr(_fe('Delete {0} Request', $type), _fe('Do you really want to delete this friend request? Click {0}here{1} if you are sure.', "<a href='{$site_config['paths']['baseurl']}/friends.php?id=$userid&amp;action=delpending&amp;type=$type&amp;targetid=$targetid&amp;sure=1&amp;h=$hash'><b>", '</b></a>'));
    }
    if ($_GET['h'] != $hash) {
        stderr(_('Error'), _('Invalid data.'));
    }
    if ($type === 'friend') {
        $db->run(');
    if (!$sure) {
        stderr("Delete $type", "Do you really want to delete a $type? Click\n<a href='{$site_config['paths']['baseurl']}/friends.php?id=$userid&amp;action=delete&amp;type=$type&amp;targetid=$targetid&amp;sure=1&amp;h=$hash'><b>here</b></a> if you are sure.", null);
    }
    if ($_GET['h'] != $hash) {
        stderr(_('Error'), _('Invalid data.'));
    }
    if ($type === 'friend') {
        $db->run(');
    app_halt('Exit called');
}

$rows = $db->fetchAll('SELECT * FROM users WHERE id = ' . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_assoc($res) or stderr(_('Error'), _('No user with that ID.'));
$HTMLOUT = '';
$i = 0;
$res = $db->run(');
$blocks = '';
if (empty($rows)) {
    $blocks = '<em>' . _('Your blocked list is empty.') . '</em>';
} else {
    while ($block = mysqli_fetch_assoc($res)) {
        $blocks .= "
            <div class='button is-small margin10'>
                <a href='{$site_config['paths']['baseurl']}/friends.php?id=$userid&amp;action=delete&amp;type=block&amp;targetid=" . (int) $block['id'] . "' class='has-text-black tooltipper' title='" . _('Delete from Blocks List') . "'>" . format_comment($block['username']) . '</a>
            </div>';
    }
}

$country = '';
$countries = countries();
foreach ($countries as $cntry) {
    if ($cntry['id'] == $user['country']) {
        $country = "<img src='{$site_config['paths']['images_baseurl']}flag/{$cntry['flagpic']}' alt='" . htmlsafechars($cntry['name']) . "'>";
        break;
    }
}
$HTMLOUT .= "
        <h1 class='has-text-centered'>" . _fe('Personal Lists for {0}', format_comment($user['username'])) . " $country</h1>
        <table class='table table-bordered table-striped top20 bottom20'>
            <thead>
                <tr>
                    <th class='w-50'>
                        <h2><a id='friends'>" . _('Friends list') . "</a></h2>
                    </th>
                    <th>
                        <h2><a id='blocks'>" . _('Blocked list') . "</a></h2>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class='w-50'><div class='masonry-small'>$friends</div></td>
                    <td><div class='level-left'>$blocks</div></td>
                </tr>
            </tbody>
        </table>
        <table class='table table-bordered table-striped top20 bottom20'>
            <thead>
                <tr>
                    <th class='w-50'><h2><a id='friendsp'>" . _('Pending List') . "</a></h2></th>
                    <th><h2><a id='friendreqs'>" . _('Awaiting Confirmation') . "</a></h2></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class='w-50'>$friendsp</td>
                    <td>$friendreqs</td>
                </tr>
            </tbody>
        </table>
        <div class='has-text-centered bottom20'>
            <a href='{$site_config['paths']['baseurl']}/users.php' class='button is-small top20'>
                " . _('Find User/Browse User List') . '
            </a>
        </div>';
$title = _('Friends');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
