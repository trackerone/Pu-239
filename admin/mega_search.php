<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $site_config;

$msg_to_analyze = (isset($_POST['msg_to_analyze']) ? htmlsafechars($_POST['msg_to_analyze']) : '');
$invite_code = (isset($_POST['invite_code']) ? htmlsafechars($_POST['invite_code']) : '');
$user_names = (isset($_POST['user_names']) ? $_POST['user_names'] : '');
$HTMLOUT = $found = $not_found = $count = $no_matches_for_this_email = $matches_for_email = $no_matches_for_this_ip = $matches_for_ip = '';
$number = 0;
$HTMLOUT .= '
        <div class="has-text-centered top20">
            <h1>' . _('Mega Search') . '</h1>
        </div>';

$HTMLOUT .= main_div('
        <div class="has-text-centered size_4 has-text-primary top10 bottom10">' . _('Analyze text - auto detect IP/Email addresses and search them in the database') . '</div>
        <div class="bg-00 round10 padding20">
            <form method="post" action="' . $_SERVER['PHP_SELF'] . '?tool=mega_search&action=mega_search" accept-charset="utf-8">
                ' . bubble(_('Text:'), _('Use this section to search emails and IPs whithin a block of text. Everything else will be ignored!')) . '
                <textarea name="msg_to_analyze" rows="20" class="w-100">' . $msg_to_analyze . '</textarea>
                <div class="has-text-centered top20">
                    <input type="submit" class="button is-small" value="' . _('Search!') . '">
                </div>
            </form>
        </div>', 'bottom20');
$HTMLOUT .= main_div('
        <div class="bg-00 round10 padding20 ">
            <form method="post" action="' . $_SERVER['PHP_SELF'] . '?tool=mega_search&action=mega_search" accept-charset="utf-8">
                ' . bubble('<b>' . _('Invite Code') . ':</b>', _('To search for an invite code, use this box. It will show you who make the code, and who used it!')) . '
                <input type="text" name="invite_code" class="w-100" value="' . $invite_code . '">
                <div class="has-text-centered top20">
                    <input type="submit" class="button is-small" value="' . _('Search!') . '">
                </div>
            </form>
        </div>', 'bottom20');
$HTMLOUT .= main_div('
        <div class="bg-00 round10 padding20">
            <form method="post" action="' . $_SERVER['PHP_SELF'] . '?tool=mega_search&action=mega_search" accept-charset="utf-8">
                ' . bubble('<b>' . _('User Names') . ':</b>', _('Use this section to search for multiple usernames. The search is not case sensitive, but you must seperate all usernames with a space! Line breaks are ignored as are any non alpha numeric charecters except - and _')) . '
                <textarea name="user_names" rows="4" class="w-100">' . $user_names . '</textarea>
                <div class="has-text-centered top20">
                    <input type="submit" class="button is-small" value="' . _('Search!') . '">
                </div>
            </form>
        </div>');

if (!empty($user_names)) {
    $searched_users = explode(',', preg_replace('/\s+/s', ',', $user_names));
    $body = '';
    $failed = [];
    foreach ($searched_users as $search_users) {
        $users = [];
        $results = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchOne($sql, [/* params */]);;

    if ($user['id'] == '') {
        $HTMLOUT .= stdmsg(_('Error'), _('No user was found! Whoever made this invite is no longer with us.'), 'top20');
    } else {
        $heading = '
                <h1 class="top10 left10">Invite Code Created By:</h1>
                <tr>
                    <th>Invite Creator</th>
                    <th>' . _('Email') . '</th>
                    <th>' . _('IP') . '</th>
                    <th>' . _('Last access') . '</th>
                    <th>' . _('Joined') . '</th>
                    <th>' . _('Up / Down') . '</th>
                    <th>' . _('Ratio') . '</th>
                    <th>' . _('Invited By') . '</th>
                </tr>';
        $body = '
                <tr>
                    <td>' . format_username($user['id']) . '</td>
                    <td>' . htmlsafechars($user['email']) . '</td>
                    <td>' . (!empty($user['ip']) ? htmlsafechars($user['ip']) : '') . '</td>
                    <td>' . get_date($user['last_access'], '') . '</td>
                    <td>' . get_date($user['registered'], '') . '</td>
                    <td><img src="' . $site_config['paths']['images_baseurl'] . 'up.png" alt="' . _('Up') . '" title="' . _('Uploaded') . '"> <span class="has-color-lime">' . mksize($user['uploaded']) . '</span>
                    ' . ($site_config['site']['ratio_free'] ? '' : '<br>
                    <img src="' . $site_config['paths']['images_baseurl'] . 'dl.png" alt="' . _('Down') . '" title="' . _('Downloaded') . '">  
                    <span class="has-color-danger">' . mksize($user['downloaded']) . '</span></td>') . '
                    <td>' . member_ratio($user['uploaded'], $user['downloaded']) . '</td>
                    <td>' . ($user['invitedby'] == 0 ? _('open signups') : format_username($user['invitedby'])) . '</td>
                </tr>';
        $HTMLOUT .= wrapper(main_table($body, $heading), 'top20');
    }

    $user_invited = [];
    $heading = $body = $users = '';
    $user_invited = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchOne($sql, [/* params */]);;

    if ($user_invited['id'] == '') {
        $HTMLOUT .= stdmsg(_('Error'), _('This invite code was either not used, or the member who used it is not longer with us.'), 'top20');
    } else {
        $heading = '
                <h1 class="top10 left10">Invite Code Used By:</h1>
                <tr>
                    <th>' . _('Invited') . '</th>
                    <th>' . _('Email') . '</th>
                    <th>' . _('IP') . '</th>
                    <th>' . _('Last access') . '</th>
                    <th>' . _('Joined') . '</th>
                    <th>' . _('Up / Down') . '</th>
                    <th>' . _('Ratio') . '</th>
                    <th>' . _('Invited By') . '</th>
                </tr>';
        $body = '
                <tr>
                    <td>' . format_username($user_invited['id']) . '</td>
                    <td>' . htmlsafechars($user_invited['email']) . '</td>
                    <td>' . (!empty($user_invited['ip']) ? htmlsafechars($user_invited['ip']) : '') . '</td>
                    <td>' . get_date($user_invited['last_access'], '') . '</td>
                    <td>' . get_date($user_invited['added'], '') . '</td>
                    <td><img src="' . $site_config['paths']['images_baseurl'] . 'up.png" alt="' . _('Up') . '" title="' . _('Uploaded') . '"> <span class="has-color-lime">' . mksize($user_invited['uploaded']) . '</span>
                    ' . ($site_config['site']['ratio_free'] ? '' : '<br>
                    <img src="' . $site_config['paths']['images_baseurl'] . 'dl.png" alt="' . _('Down') . '" title="' . _('Downloaded') . '">  
                    <span class="has-color-danger">' . mksize($user_invited['downloaded']) . '</span></td>') . '
                    <td>' . member_ratio($user_invited['uploaded'], $user_invited['downloaded']) . '</td>
                    <td>' . ($user_invited['invitedby'] == 0 ? _('open signups') : format_username($user_invited['receiver'])) . '</td>
                </tr>';
        $HTMLOUT .= wrapper(main_table($body, $heading));
    }
}
$title = _('Mega Search');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
