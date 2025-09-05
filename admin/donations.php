<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_html.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
$HTMLOUT = '';
global $container, $site_config;

$fluent = $container->get(Database::class);
if (isset($_GET['total_donors'])) {
    $total_donors = (int) $_GET['total_donors'];
    if ($total_donors != '1') {
        stderr(_('Error'), _('I smell a rat!'));
    }
    $count = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;
} else {
    $count = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;
}
if ($count > $perpage) {
    $HTMLOUT .= $pager['pagertop'];
}

$HTMLOUT .= "
    <ul class='level-center bg-06'>
        <li class='is-link margin10'>
            <a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=donations&amp;action=donations'>" . _('Current Donors') . "</a>
        </li>
        <li class='is-link margin10'>
            <a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=donations&amp;action=donations&amp;total_donors=1'>" . _('All Donations') . "</a>
        </li>
    </ul>
    <h1 class='has-text-centered'>Site Donations</h1>";
$heading = '
    <tr>
        <th>' . _('ID') . '</th>
        <th>' . _('Username') . '</th>
        <th>' . _('E-mail') . '</th>
        <th>' . _('Joined') . '</th>
        <th>' . _('Donor Until?') . '</th>
        <th>' . _('Current') . '</th>
        <th>' . _('Total') . '</th>
        <th>' . _('PM') . '</th>
    </tr>';
$body = '';
foreach ($sql as $arr) {
    $body .= "
    <tr>
        <td>{$arr['id']}</td>
        <td>" . format_username((int) $arr['id']) . "</td>
        <td><a class='is-link' href='mailto:" . htmlsafechars($arr['email']) . "'>" . htmlsafechars($arr['email']) . "</a></td>
        <td><span class='size_3'>" . get_date((int) $arr['registered'], 'DATE') . '</span></td>
        <td>';
    $donoruntil = (int) $arr['donoruntil'];
    if ($donoruntil == 0) {
        $body .= 'n/a';
    } else {
        $body .= '<span class="size_3">' . get_date((int) $arr['donoruntil'], 'DATE') . ' [ ' . mkprettytime($donoruntil - TIME_NOW) . ' ] ' . _('To go') . '...</span>';
    }
    setlocale(LC_MONETARY, 'en_US.UTF-8');
    $body .= '
        </td>
        <td><b>' . money_format('%.2n', (float) $arr['donated']) . '</td>
        <td><b>' . money_format('%.2n', (float) $arr['total_donated']) . "</td>
        <td>
            <a class='is-link' href='{$site_config['paths']['baseurl']}/messages.php?action=send_message&amp;receiver=" . (int) $arr['id'] . "'>" . _('PM') . '</a>
        </td>
    </tr>';
}
if ($count === 0) {
    $body = '<td colspan="8">No Donors</td>';
}
$HTMLOUT .= main_table($body, $heading);
if ($count > $perpage) {
    $HTMLOUT .= $pager['pagerbottom'];
}

$title = _('Donations');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
