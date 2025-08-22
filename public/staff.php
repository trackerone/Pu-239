<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
check_user_status();
$image = placeholder_image();
global $container, $site_config;

$support = $mods = $admin = $sysop = [];
$htmlout = $firstline = '';
$fluent = $container->get(Database::class);
$query = $fluent->from('users')
                ->select(null)
                ->select('users.id')
                ->select('users.class')
                ->select('users.perms')
                ->select('users.last_access')
                ->select('users.support')
                ->select('users.supportfor')
                ->select('users.country')
                ->select('countries.flagpic')
                ->select('countries.name as flagname')
                ->leftJoin('countries ON countries.id=users.country')
                ->where('users.status = 0 AND (users.class >= ? OR users.support = "yes")', UC_STAFF)
                ->orderBy('class DESC')
                ->orderBy('username');

$staffs = [];
foreach ($query as $arr2) {
    if ($arr2['support'] === 'yes') {
        $support[] = $arr2;
    } else {
        $staffs[strtolower($site_config['class_names'][$arr2['class']])][] = $arr2;
    }
}

/**
 * @param $staff_array
 * @param $staffclass
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 *
 * @return string|null
 */
function DoStaff($staff_array, $staffclass)
{
    global $site_config;

    $image = placeholder_image();
    if (empty($staff_array)) {
        return null;
    }
    $htmlout = $body = '';
    $dt = TIME_NOW - 180;

    $htmlout .= "
                <h2 class='left10 top20'>{$staffclass}</h2>";
    foreach ($staff_array as $staff) {
        $body .= '
                    <tr>';
        $flagpic = !empty($staff['flagpic']) ? "{$site_config['paths']['images_baseurl']}flag/{$staff['flagpic']}" : '';
        $flagname = !empty($staff['flagname']) ? $staff['flagname'] : '';
        $flag = !empty($flagpic) ? "<img src='{$image}' data-src='$flagpic' alt='" . htmlsafechars($flagname) . "' class='emoticon lazy'>" : '';
        $body .= '
                        <td>' . format_username((int) $staff['id']) . "</td>
                        <td><img src='{$image}' data-src='{$site_config['paths']['images_baseurl']}" . ($staff['last_access'] > $dt && get_anonymous($staff['id']) ? 'online.png' : 'offline.png') . "' alt='' class='emoticon lazy'></td>" . "
                        <td><a href='{$site_config['paths']['baseurl']}/messages.php?action=send_message&amp;receiver=" . (int) $staff['id'] . '&amp;returnto=' . urlencode($_SERVER['REQUEST_URI']) . "'><i class='icon-mail icon tooltipper' aria-hidden='true' title='Personal Message'></i></a></td>" . "
                        <td>$flag</td>
                    </tr>";
    }

    return $htmlout . main_table($body);
}

foreach ($staffs as $key => $value) {
    if (!empty($key)) {
        $htmlout .= DoStaff($value, ucfirst($key) . 's');
    }
}

$dt = TIME_NOW - 180;
if (!empty($support)) {
    $body = '';
    foreach ($support as $a) {
        $flagpic = !empty($staff['flagpic']) ? "{$site_config['paths']['images_baseurl']}flag/{$staff['flagpic']}" : '';
        $flagname = !empty($staff['flagname']) ? $staff['flagname'] : '';
        $body .= '
                <tr>
                    <td>' . format_username((int) $a['id']) . "</td>
                    <td><img src='{$image}' data-src='{$site_config['paths']['images_baseurl']}" . ($a['last_access'] > $dt ? 'online.png' : 'offline.png') . "' alt='' class='emoticon lazy'></td>
                    <td><a href='{$site_config['paths']['baseurl']}messages.php?action=send_message&amp;receiver=" . (int) $a['id'] . "'><i class='icon-mail icon tooltipper' aria-hidden='true' title='" . _('Personal Message') . "'></i></a></td>
                    <td><img src='{$image}' data-src='$flagpic' alt='" . htmlsafechars($flagname) . "' class='emoticon lazy'></td>
                    <td>" . htmlsafechars($a['supportfor']) . '</td>
                </tr>';
    }
    $htmlout .= "
            <h2 class='left10 top20'>" . _('First Line Support') . '</h2>';
    $heading = "
                    <tr>
                        <th class='staff_username' colspan='5'>" . _('General support questions should be directed 
to these users.<br>
Note that they are volunteers, giving away their time and effort to help 
you. Treat them accordingly. (Languages listed are those besides English.)') . "<br><br></th>
                    </tr>
                    <tr>
                        <th class='staff_username'>" . _('Username') . '</th>
                        <th>' . _('Active') . '</th>
                        <th>' . _('Contact') . '</th>
                        <th>' . _('Language') . '</th>
                        <th>' . _('Support for:') . '</th>
                    </tr>';
    $htmlout .= main_table($body, $heading);
}
$title = _('Staff');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlout) . stdfoot();
