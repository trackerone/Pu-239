<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Cache;
use Pu239\Session;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $container, $site_config, $CURUSER;

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
$HTMLOUT = $delt_link = $type = $count2 = '';

/**
 * @param $ts
 *
 * @return string
 */
function round_time($ts)
{
    $mins = floor($ts / 60);
    $hours = floor($mins / 60);
    $mins -= $hours * 60;
    $days = floor($hours / 24);
    $hours -= $days * 24;
    $weeks = floor($days / 7);
    $days -= $weeks * 7;
    if ($weeks > 0) {
        return "$weeks week" . ($weeks > 1 ? 's' : '');
    }
    if ($days > 0) {
        return "$days day" . ($days > 1 ? 's' : '');
    }
    if ($hours > 0) {
        return "$hours hour" . ($hours > 1 ? 's' : '');
    }
    if ($mins > 0) {
        return "$mins min" . ($mins > 1 ? 's' : '');
    }

    return '< 1 min';
}

if (isset($_GET['id'])) {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
    if (!is_valid_id($id)) {
        stderr(_('Error'), _('Invalid ID!'));
    }
}
if (isset($_GET['type'])) {
    $type = ($_GET['type'] ? htmlsafechars((string) $_GET['type']) : htmlsafechars((string) $_POST['type']));
    $typesallowed = [
        'User',
        'Comment',
        'Request_Comment',
        'Offer_Comment',
        'Request',
        'Offer',
        'Torrent',
        'Hit_And_Run',
        'Post',
    ];
    if (!in_array($type, $typesallowed)) {
        stderr(_('Error'), _('Invalid report type!'));
    }
}
$cache = $container->get(Cache::class);
if ((isset($_GET['deal_with_report'])) || (isset($_POST['deal_with_report']))) {
    if (!is_valid_id((int) $_POST['id'])) {
        stderr(_('Error'), _('Invalid ID.'));
    }
    $how_delt_with = 'how_delt_with = ' . sqlesc($_POST['body']);
    $when_delt_with = 'when_delt_with = ' . sqlesc(TIME_NOW);
    sql_query("UPDATE reports SET delt_with = 1, $how_delt_with, $when_delt_with , who_delt_with_it =" . sqlesc($CURUSER['id']) . ' WHERE delt_with!=1 AND id =' . sqlesc($_POST['id'])) or sqlerr(__FILE__, __LINE__);
    $cache->delete('new_report_');
}

$HTMLOUT .= "<h1 class='has-text-centered'>" . _('Active Reports') . '</h1>';

if ((isset($_GET['delete'])) && ($CURUSER['class'] >= UC_MAX)) {
    $res = sql_query('DELETE FROM reports WHERE id =' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
    $cache->delete('new_report_');
    $session = $container->get(Session::class);
    $session->set('is-success', _('Report Deleted!'));
}

$res = sql_query('SELECT count(id) FROM reports') or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_array($res);
$count = (int) $row[0];
$perpage = 15;
$pager = pager($perpage, $count, "{$site_config['paths']['baseurl']}/staffpanel.php?tool=reports&amp;");
if ($count === 0) {
    $HTMLOUT .= stdmsg('', _('No Reports, they are all playing nice!'), 'bottom20');
} else {
    $HTMLOUT .= $count > $perpage ? $pager['pagertop'] : '';
    $HTMLOUT .= "
        <form method='post' action='{$_SERVER['PHP_SELF']}?tool=reports&amp;action=reports&amp;deal_with_report=1' enctype='multipart/form-data' accept-charset='utf-8'>";
    $header = '
        <tr>
            <th>' . _('Added') . '</th>
            <th>' . _('Reported by') . '</th>
            <th>' . _('Reporting What') . '</th>
            <th>' . _('Type') . '</th>
            <th>' . _('Reason') . '</th>
            <th>' . _('Dealt With') . '</th>
            <th>' . _('Deal With It') . '</th>' . ($CURUSER['class'] >= UC_MAX ? '
            <th>' . _('Delete') . '</th>' : '') . '
        </tr>';

    $res_info = sql_query("SELECT reports.id, reports.reported_by, reports.reporting_what, reports.reporting_type, reports.reason, reports.who_delt_with_it, reports.delt_with, reports.added, reports.how_delt_with, reports.when_delt_with, reports.2nd_value, users.username FROM reports INNER JOIN users on reports.reported_by = users.id ORDER BY id DESC {$pager['limit']}");
    $body = '';
    while ($arr_info = mysqli_fetch_assoc($res_info)) {
        $added = (int) $arr_info['added'];
        $solved_date = (int) $arr_info['when_delt_with'];
        if ($solved_date == '0') {
            $solved_in = ' [N/A]';
            $solved_color = 'pink';
        } else {
            $solved_in_wtf = $arr_info['when_delt_with'] - $arr_info['added'];
            $solved_in = '&#160;[' . round_time($solved_in_wtf) . ']';
            $solved_color = 'purple';
            if ($solved_in_wtf > 4 * 3600) {
                $solved_color = 'red';
            } elseif ($solved_in_wtf > 2 * 3600) {
                $solved_color = 'yellow';
            } elseif ($solved_in_wtf <= 3600) {
                $solved_color = 'green';
            }
        }

        if ($arr_info['delt_with']) {
            $res_who = sql_query('SELECT username FROM users WHERE id = ' . sqlesc($arr_info['who_delt_with_it']));
            $arr_who = mysqli_fetch_assoc($res_who);
            $dealtwith = "<span style='color: {$solved_color};'><b>" . _('Yes') . ' - </b> </span> ' . _('by') . ': ' . format_username((int) $arr_info['who_delt_with_it']) . '<br>' . _('in') . ": <span style='color: {$solved_color};'>{$solved_in}</span>";
            $checkbox = "<input type='radio' name='id' value='" . $arr_info['id'] . "' disabled>";
        } else {
            $dealtwith = "<span class='has-text-danger'><b>" . _('No') . '</b></span>';
            $checkbox = "<input type='radio' name='id' value='" . $arr_info['id'] . "'>";
        }

        if ($arr_info['reporting_type'] != '') {
            switch ($arr_info['reporting_type']) {
                case 'User':
                    $link_to_thing = format_username((int) $arr_info['reporting_what']);
                    break;

                case 'Comment':
                    $res_who2 = sql_query('SELECT comments.user, users.username, torrents.id FROM comments, users, torrents WHERE comments.user = users.id AND comments.id=' . sqlesc($arr_info['reporting_what']));
                    $arr_who2 = mysqli_fetch_assoc($res_who2);
                    $link_to_thing = "<a class='is-link' href='{$site_config['paths']['baseurl']}/details.php?id=" . $arr_who2['id'] . '&amp;viewcomm=' . $arr_info['reporting_what'] . '#comm' . $arr_info['reporting_what'] . "'><b>" . format_comment($arr_who2['username']) . '</b></a>';
                    break;

                case 'Request_Comment':
                    $res_who2 = sql_query('SELECT comments.request, comments.user, users.username FROM comments, users WHERE comments.user = users.id AND comments.id=' . sqlesc($arr_info['reporting_what']));
                    $arr_who2 = mysqli_fetch_assoc($res_who2);
                    $link_to_thing = "<a class='is-link' href='{$site_config['paths']['baseurl']}/requests.php?id=" . $arr_who2['request'] . '&amp;req_details=1&amp;viewcomm=' . $arr_info['reporting_what'] . '#comm' . $arr_info['reporting_what'] . "'><b>" . format_comment($arr_who2['username']) . '</b></a>';
                    break;

                case 'Offer_Comment':
                    $res_who2 = sql_query('SELECT comments.offer, comments.user, users.username FROM comments, users WHERE comments.user = users.id AND comments.id = ' . sqlesc($arr_info['reporting_what']));
                    $arr_who2 = mysqli_fetch_assoc($res_who2);
                    $link_to_thing = "<a class='is-link' href='{$site_config['paths']['baseurl']}/offers.php?id=" . $arr_who2['offer'] . '&amp;off_details=1&amp;viewcomm=' . $arr_info['reporting_what'] . '#comm' . $arr_info['reporting_what'] . "'><b>" . format_comment($arr_who2['username']) . '</b></a>';
                    break;

                case 'Request':
                    $res_who2 = sql_query('SELECT name FROM requests WHERE id = ' . sqlesc($arr_info['reporting_what']));
                    $arr_who2 = mysqli_fetch_assoc($res_who2);
                    $link_to_thing = "<a class='is-link' href='{$site_config['paths']['baseurl']}/requests.php?id=" . $arr_info['reporting_what'] . "&amp;req_details=1'><b>" . format_comment($arr_who2['name']) . '</b></a>';
                    break;

                case 'Offer':
                    $res_who2 = sql_query('SELECT name FROM offers WHERE id = ' . sqlesc($arr_info['reporting_what']));
                    $arr_who2 = mysqli_fetch_assoc($res_who2);
                    $link_to_thing = "<a class='is-link' href='{$site_config['paths']['baseurl']}/offers.php?id=" . $arr_info['reporting_what'] . "&amp;off_details=1'><b>" . format_comment($arr_who2['name']) . '</b></a>';
                    break;

                case 'Torrent':
                    $res_who2 = sql_query('SELECT name FROM torrents WHERE id =' . sqlesc($arr_info['reporting_what']));
                    $arr_who2 = mysqli_fetch_assoc($res_who2);
                    $name = !empty($arr_who2['name']) ? format_comment($arr_who2['name']) : 'Torrent Unknown';
                    $link_to_thing = "<a class='is-link' href='{$site_config['paths']['baseurl']}/details.php?id=" . $arr_info['reporting_what'] . "'><b>" . $name . '</b></a>';
                    break;

                case 'Hit_And_Run':
                    $res_who2 = sql_query('SELECT users.username, torrents.name, r.2nd_value FROM users, torrents LEFT JOIN reports AS r ON r.2nd_value = torrents.id WHERE users.id = ' . sqlesc($arr_info['reporting_what']));
                    $arr_who2 = mysqli_fetch_assoc($res_who2);
                    $name = !empty($arr_who2['name']) ? format_comment($arr_who2['name']) : '';
                    $link_to_thing = '<b>' . _('user') . ':</b> ' . format_username((int) $arr_info['reporting_what']) . '<br>' . _('hit and run on') . ":<br> <a class='is-link' href='{$site_config['paths']['baseurl']}/details.php?id=" . $arr_info['2nd_value'] . "&amp;page=0#snatched'><b>" . $name . '</b></a>';
                    break;

                case 'Post':
                    $res_who2 = sql_query('SELECT topic_name FROM topics WHERE id =' . sqlesc($arr_info['2nd_value']));
                    $arr_who2 = mysqli_fetch_assoc($res_who2);
                    $name = !empty($arr_who2['topic_name']) ? format_comment($arr_who2['topic_name']) : '';
                    $link_to_thing = '<b>' . _('post') . ":</b> <a class='is-link' href='{$site_config['paths']['baseurl']}/forums.php?action=view_topic&amp;topic_id=" . $arr_info['2nd_value'] . '&amp;page=last#' . $arr_info['reporting_what'] . "'><b>" . $name . '</b></a>';
                    break;
            }
        }
        $body .= '
        <tr>
            <td>' . get_date((int) $arr_info['added'], 'DATE', 0, 1) . '</td>
            <td>' . format_username((int) $arr_info['reported_by']) . "</td>
            <td>{$link_to_thing}</td>
            <td><b>" . str_replace('_', ' ', $arr_info['reporting_type']) . '</b>' . '</td>
            <td>' . $arr_info['reason'] . "</td>
            <td>{$dealtwith} {$delt_link}</td>
            <td>{$checkbox}</td>" . ($CURUSER['class'] >= UC_MAX ? "
            <td><a class='is-link' href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=reports&amp;action=reports&amp;id=" . $arr_info['id'] . "&amp;delete=1'>
                    <i class='icon-trash-empty tooltipper has-text-danger' title='" . _('Delete') . "' aria-hidden='true'></i>
                </a>
            </td>" : '') . '
        </tr>';
        if ($arr_info['how_delt_with']) {
            $body .= "
        <tr>
            <td colspan='" . ($CURUSER['class'] >= UC_MAX ? 8 : 7) . "'><b>" . _fe('Dealt with by {0}', format_comment($arr_who['username'])) . ':</b> ' . get_date((int) $arr_info['when_delt_with'], 'LONG', 0, 1) . "</td>
        </tr>
        <tr>
            <td colspan='" . ($CURUSER['class'] >= UC_MAX ? 8 : 7) . "'>" . format_comment($arr_info['how_delt_with']) . '<br><br></td>
        </tr>';
        }
    }
    $HTMLOUT .= main_table($body, $header);
    $HTMLOUT .= $count > $perpage ? $pager['pagerbottom'] : '';
}

if ($count > 0) {
    $HTMLOUT .= main_div(_fe('How {0} dealt with this report:<br>Please explain below how this Report has been dealt with.', $CURUSER['username']) . BBcode('', 'top20', 200) . "
    <input type='submit' class='button is-small margin20' value='" . _('Confirm') . "'>
    </form>", 'top20 has-text-centered', 'padding20');
}
$title = _('Active Reports');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
