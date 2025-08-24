<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

use Pu239\Cache;
use Pu239\Session;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $container;
$db = $container->get(Database::class);, $site_config, $CURUSER;

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
    $db->run(");
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
            $res_who = $db->run(');
}
$title = _('Active Reports');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
