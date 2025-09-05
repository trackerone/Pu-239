<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;
use Pu239\Session;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_password.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
$user = check_user_status();
global $container, $site_config;

$HTMLOUT = '';
$fluent = $container->get(Database::class);
$session = $container->get(Session::class);
$do = isset($_GET['do']) ? $_GET['do'] : (isset($_POST['do']) ? $_POST['do'] : '');
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : '0');
$link = isset($_GET['link']) ? $_GET['link'] : (isset($_POST['link']) ? $_POST['link'] : '');
$sure = isset($_GET['sure']) && $_GET['sure'] === 'yes' ? 'yes' : 'no';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $do === 'addpromo') {
    $promoname = isset($_POST['promoname']) ? $_POST['promoname'] : '';
    if (empty($promoname)) {
        stderr(_('Error'), 'No name for the promo');
    }
    $days_valid = isset($_POST['days_valid']) ? (int) $_POST['days_valid'] : 0;
    if ($days_valid === 0) {
        stderr(_('Error'), "Link will be valid for 0 days ? I don't think so!");
    }
    $max_users = isset($_POST['max_users']) ? (int) $_POST['max_users'] : 0;
    if ($max_users === 0) {
        stderr(_('Error'), 'Max users cant be 0 i think you missed that!');
    }
    $bonus_upload = isset($_POST['bonus_upload']) ? (int) $_POST['bonus_upload'] : 0;
    $bonus_invites = isset($_POST['bonus_invites']) ? (int) $_POST['bonus_invites'] : 0;
    $bonus_karma = isset($_POST['bonus_karma']) ? (int) $_POST['bonus_karma'] : 0;
    if ($bonus_upload === 0 && $bonus_invites === 0 && $bonus_karma === 0) {
        stderr(_('Error'), 'No gift for the new users? Give them some gifts :D');
    }
    $token = make_password(32);
    $values = [
        'name' => $promoname,
        'added' => TIME_NOW,
        'days_valid' => $days_valid,
        'max_users' => $max_users,
        'link' => $token,
        'creator' => $user['id'],
        'bonus_upload' => $bonus_upload,
        'bonus_invites' => $bonus_invites,
        'bonus_karma' => $bonus_karma,
    ];
    $promo_id = // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;
    if (empty($promo_id)) {
        stderr(_('Error'), 'Something wrong happened, please retry');
    } else {
        $session->set('is-success', 'The promo link [b]' . htmlsafechars($promoname) . '[/b] was added!');
        unset($_POST);
    }
} elseif ($do === 'delete' && $id > 0) {
    $r = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;
    if (empty($r)) {
        stderr(_('Error'), _fe('There are no promotions. If you want to make one click {0}here{1}', '<a href="' . $_SERVER['PHP_SELF'] . '?tool=promo&amp;do=addpromo">', '</a>'), 'bottom20');
    } else {
        $HTMLOUT .= '
                <div class="has-text-centered bottom20"> 
                    <h1>Current Promos</h1>
                    <a href="' . $_SERVER['PHP_SELF'] . '?tool=promo&amp;do=addpromo"><span class="size_3">Add promo</span></a>
                </div>';
        $heading = "
            <tr class='has-text-centered'>
                <th class='has-text-centered' rowspan='2'>Promo</th>
                <th class='has-text-centered' rowspan='2'>Added</th>
                <th class='has-text-centered' rowspan='2'>Valid Till</th>
                <th class='has-text-centered' colspan='2'>Users</th>
                <th class='has-text-centered' colspan='3'>Bonuses</th>
                <th class='has-text-centered' rowspan='2'>Added by</th>       
                <th class='has-text-centered' rowspan='2'>Remove</th>       
            </tr>
            <tr>
                <th class='has-text-centered'>max</th>
                <th class='has-text-centered'>till now</th>
                <th class='has-text-centered'>upload</th>
                <th class='has-text-centered'>invites</th>
                <th class='has-text-centered'>karma</th>
            </tr>";
        $body = '';
        foreach ($r as $ar) {
            $active = $ar['max_users'] === $ar['accounts_made'] || $ar['added'] + (86400 * $ar['days_valid']) < TIME_NOW ? false : true;
            $body .= '
            <tr class="tooltipper"' . (!$active ? ' title="This promo has ended"' : '') . '>
                <td>' . (htmlsafechars($ar['name'])) . "<br><input type='text' " . (!$active ? 'disabled' : '') . " value='" . ($site_config['paths']['baseurl'] . '/signup.php?promo=' . $ar['link']) . "' name='" . (htmlsafechars($ar['name'])) . "' onclick='select();' class='w-100'></td>
                <td class='has-text-centered'>" . get_date($ar['added'], 'LONG') . "</td>
                <td class='has-text-centered'>" . get_date($ar['added'] + (86400 * $ar['days_valid']), 'LONG', 1, 0) . "</td>
                <td class='has-text-centered'>" . $ar['max_users'] . "</td>
                <td class='has-text-centered'>" . ($ar['accounts_made'] > 0 ? '<a href="' . $_SERVER['PHP_SELF'] . '?tool=promo&amp;do=accounts&amp;link=' . $ar['link'] . '">' . $ar['accounts_made'] . '</a>' : 0) . "</td>
                <td class='has-text-centered'>" . mksize($ar['bonus_upload'] * 1073741824) . "</td>
                <td class='has-text-centered'>" . number_format($ar['bonus_invites']) . "</td>
                <td class='has-text-centered'>" . number_format($ar['bonus_karma']) . "</td>
                <td class='has-text-centered'>" . format_username($ar['creator']) . "</a></td>
                <td class='has-text-centered'><a href='" . $_SERVER['PHP_SELF'] . '?tool=promo&amp;do=delete&amp;id=' . $ar['id'] . "'><i class='icon-trash-empty icon has-text-danger'></i></a></td>
            </tr>";
        }
        $HTMLOUT .= main_table($body, $heading);
        $title = _('Current Promos');
        $breadcrumbs = [
            "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
            "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
        ];
        echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
    }
}
