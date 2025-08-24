<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once INCL_DIR . 'function_pager.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $site_config;

$HTMLOUT = $where = '';
$search = isset($_POST['search']) ? strip_tags($_POST['search']) : '';
if (isset($_GET['search'])) {
    $search = strip_tags($_GET['search']);
}
if (!empty($search)) {
    $where = 'WHERE txt LIKE ' . sqlesc("%$search%") . '';
}
//== Delete items older than 1 month
$secs = 30 * 86400;
$db->run(');
$HTMLOUT = '';
$rows = $db->fetchAll("SELECT added, txt FROM infolog $where ORDER BY added DESC {$pager['limit']}");
$HTMLOUT .= "
    <h1 class='has-text-centered'>" . _('Staff actions log') . "</h1>
    <div class='has-text-centered bottom20'>
        <form method='post' action='{$_SERVER['PHP_SELF']}?tool=sysoplog&amp;action=sysoplog' enctype='multipart/form-data' accept-charset='utf-8'>
            <input type='text' name='search' size='40' value='' placeholder='" . _('Search log') . "'>
            <input type='submit' value='" . _('Search log') . "' class='button is-small'>
        </form>
    </div>";
if ($count > $perpage) {
    $HTMLOUT .= $pager['pagertop'];
}
if (empty($rows)) {
    $HTMLOUT .= main_div("<div class='padding20'>" . _('No records found') . '</div>');
} else {
    $heading = '
      <tr>
          <th>' . _('Date') . '</th>
          <th>' . _('Time') . '</th>
          <th>' . _('Event') . '</th>
      </tr>';
    $body = '';
    $log_events = [];
    $colors = [];
    foreach ($rows as $arr) {
        $txt = substr($arr['txt'], 0, 50);
        if (!in_array($txt, $log_events)) {
            $color = random_color();
            while (in_array($color, $colors)) {
                $color = random_color();
            }
            $log_events[] = $txt;
            $colors[] = $color;
        }
        $key = array_search($txt, $log_events);
        $color = $colors[$key];
        $date = get_date((int) $arr['added'], 'DATE');
        $time = get_date((int) $arr['added'], 'LONG', 0, 1);
        $body .= "
        <tr>
            <td style='background-color: $color;'>
                <span class='has-text-black'>{$date}</span>
            </td>
            <td style='background-color: $color;'>
                <span class='has-text-black'>{$time}</span>
            </td>
            <td style='background-color: $color;'>
                <span class='has-text-black'>{$arr['txt']}</span>
            </td>
        </tr>";
    }
    $HTMLOUT .= main_table($body, $heading);
}

if ($count > $perpage) {
    $HTMLOUT .= $pager['pagerbottom'];
}
$title = _('Sysop Log');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
