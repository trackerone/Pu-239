<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


declare(strict_types = 1);

use Pu239\Database;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_pager.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $site_config;

$HTMLOUT = '';

/**
 * @param $st
 *
 * @return string
 */
function get_snatched_color($st)
{
    $secs = $st;
    $mins = floor($st / 60);
    $hours = floor($mins / 60);
    $days = floor($hours / 24);
    $week = floor($days / 7);
    $month = floor($week / 4);
    if ($month > 0) {
        $week_elapsed = floor(($st - ($month * 4 * 7 * 24 * 60 * 60)) / (7 * 24 * 60 * 60));
        $days_elapsed = floor(($st - ($week * 7 * 24 * 60 * 60)) / (24 * 60 * 60));
        //$hours_elapsed = floor(($st - ($days * 24 * 60 * 60)) / (60 * 60));
        //$mins_elapsed = floor(($st - ($hours * 60 * 60)) / 60);
        //$secs_elapsed = floor($st - $mins * 60);

        return "<span class='has-text-success'><b>$month months.<br>$week_elapsed W. $days_elapsed D.</b></span>";
    }
    if ($week > 0) {
        $days_elapsed = floor(($st - ($week * 7 * 24 * 60 * 60)) / (24 * 60 * 60));
        $hours_elapsed = floor(($st - ($days * 24 * 60 * 60)) / (60 * 60));
        $mins_elapsed = floor(($st - ($hours * 60 * 60)) / 60);
        $secs_elapsed = floor($st - $mins * 60);

        return "<span class='has-text-success'><b>$week W. $days_elapsed D.<br>$hours_elapsed:$mins_elapsed:$secs_elapsed</b></span>";
    }
    if ($days > 2) {
        $hours_elapsed = floor(($st - ($days * 24 * 60 * 60)) / (60 * 60));
        $mins_elapsed = floor(($st - ($hours * 60 * 60)) / 60);
        $secs_elapsed = floor($st - $mins * 60);

        return "<span class='has-text-success'><b>$days D.<br>$hours_elapsed:$mins_elapsed:$secs_elapsed</b></span>";
    }
    if ($days > 1) {
        $hours_elapsed = floor(($st - ($days * 24 * 60 * 60)) / (60 * 60));
        $mins_elapsed = floor(($st - ($hours * 60 * 60)) / 60);
        $secs_elapsed = floor($st - $mins * 60);

        return "<span class='is-success'><b>$days D.<br>$hours_elapsed:$mins_elapsed:$secs_elapsed</b></span>";
    }
    if ($days > 0) {
        $hours_elapsed = floor(($st - ($days * 24 * 60 * 60)) / (60 * 60));
        $mins_elapsed = floor(($st - ($hours * 60 * 60)) / 60);
        $secs_elapsed = floor($st - $mins * 60);

        return "<span style='color: #CCFFCC;'><b>$days D.<br>$hours_elapsed:$mins_elapsed:$secs_elapsed</b></span>";
    }
    if ($hours > 12) {
        $mins_elapsed = floor(($st - ($hours * 60 * 60)) / 60);
        $secs_elapsed = floor($st - $mins * 60);

        return "<span style='color: yellow;'><b>$hours:$mins_elapsed:$secs_elapsed</b></span>";
    }
    if ($hours > 0) {
        $mins_elapsed = floor(($st - ($hours * 60 * 60)) / 60);
        $secs_elapsed = floor($st - $mins * 60);

        return "<span class='has-text-danger'><b>$hours:$mins_elapsed:$secs_elapsed</b></span>";
    }
    if ($mins > 0) {
        $secs_elapsed = floor($st - $mins * 60);

        return "<span class='has-text-danger'><b>0:$mins:$secs_elapsed</b></span>";
    }
    if ($secs > 0) {
        return "<span class='has-text-danger'><b>0:0:$secs</b></span>";
    }

    return "<span class='has-text-danger'><b>" . _('None') . '<br>' . _('reported') . '</b></span>';
}

global $container;

$fluent = $container->get(Database::class);
$count = $fluent->from('snatched')
                ->select(null)
                ->select('COUNT(id) AS count')
                ->fetch('count');

$valid_sort = [
    'id',
    'userid',
    'name',
    'hit_and_run',
    'mark_of_cain',
    'timesann',
    'uploaded',
    'downloaded',
    'seedtime',
    'leechtime',
    'start_date',
    'complete_date',
    'seeder',
];
$column = isset($_GET['sort'], $valid_sort[$_GET['sort']]) ? $valid_sort[$_GET['sort']] : 'start_date';
$pagerlink = $ascdesc = '';
$type = isset($_GET['type']) ? $_GET['type'] : 'desc';
foreach ($valid_sort as $key => $value) {
    if ($value === $column) {
        switch (htmlsafechars($type)) {
            case 'desc':
                $ascdesc = 'DESC';
                $linkascdesc = 'desc';
                break;

            default:
                $ascdesc = '';
                $linkascdesc = 'asc';
                break;
        }
        $pagerlink = "sort={$key}&amp;type={$linkascdesc}&amp;";
    }
}
for ($i = 0; $i <= count($valid_sort); ++$i) {
    if (isset($_GET['sort']) && (int) $_GET['sort'] === $i) {
        $link[$i] = isset($type) && $type === 'desc' ? 'asc' : 'desc';
    } else {
        $link[$i] = 'desc';
    }
}
$HTMLOUT .= "
    <h1 class='has-text-centered'>" . _('All snatched torrents') . "</h1>
    <div class='has-text-centered size_4 bottom20'>" . _pfe('We currently have {0} snatched torrent', 'We currently have {0} snatched torrents', $count) . '</div>';
$snatchedperpage = 25;
$pager = pager($snatchedperpage, $count, $_SERVER['PHP_SELF'] . '?tool=snatched_torrents&amp;' . $pagerlink);
if ($count > $snatchedperpage) {
    $HTMLOUT .= $pager['pagertop'];
}
$snatched = $fluent->from('snatched AS sn')
                   ->select('t.name')
                   ->leftJoin('torrents AS t ON sn.torrentid = t.id')
                   ->orderBy("$column $ascdesc")
                   ->limit($pager['pdo']['limit'])
                   ->offset($pager['pdo']['offset']);
if ($count > 0) {
    $heading = "
    <tr>
        <th class='has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=1&amp;type={$link[1]}'>" . _('Username') . "</a></th>
        <th class='min-150 has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=2&amp;type={$link[2]}'>" . _('Torrent name') . "</a></th>
        <th class='has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=3&amp;type={$link[3]}'>" . _('HitnRun') . "</a></th>
        <th class='has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=4&amp;type={$link[4]}'>" . _('Mark of cain') . "</a></th>
        <th class='has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=5&amp;type={$link[5]}'>" . _('Announced') . "</a></th>
        <th class='has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=6&amp;type={$link[6]}'>" . _('Uploaded') . "</a></th>
        <th class='has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=7&amp;type={$link[7]}'>" . _('Downloaded') . "</a></th>
        <th class='has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=8&amp;type={$link[8]}'>" . _('Seedtime') . "</a></th>
        <th class='has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=9&amp;type={$link[9]}'>" . _('Leechtime') . "</a></th>
        <th class='has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=10&amp;type={$link[10]}'>" . _('Start date') . "</a></th>
        <th class='has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=11&amp;type={$link[11]}'>" . _('End date') . "</a></th>
        <th class='has-text-centered'><a href='{$_SERVER['PHP_SELF']}?tool=snatched_torrents&amp;sort=12&amp;type={$link[12]}'>" . _('Seeder') . '</a></th>
    </tr>';
    $body = '';
    foreach ($snatched as $row) {
        $smallname = format_comment($row['name']);
        $body .= '
    <tr>
        <td>' . format_username((int) $row['userid']) . "</td>
        <td><a href='{$site_config['paths']['baseurl']}/details.php?id=" . (int) $row['torrentid'] . "'><b>" . $smallname . '</b></a></td>
        <td class="has-text-centered"><b>' . get_date((int) $row['hit_and_run'], 'LONG', 0, 1) . '</b></td>
        <td class="has-text-centered"><b>' . format_comment($row['mark_of_cain']) . '</b></td>
        <td class="has-text-centered"><b>' . $row['timesann'] . '</b></td>
        <td class="has-text-centered"><b><span class="tooltipper" title="Real Upload: ' . mksize($row['real_uploaded']) . '">' . mksize($row['uploaded']) . '</span></b></td>
        <td class="has-text-centered"><b><span class="tooltipper" title="Real Download: ' . mksize($row['real_downloaded']) . '">' . mksize($row['downloaded']) . '</span></b></td>
        <td class="has-text-centered"><b>' . get_snatched_color($row['seedtime']) . '</b></td>
        <td class="has-text-centered"><b>' . mkprettytime($row['leechtime']) . '</b></td>
        <td class="has-text-centered"><b>' . get_date((int) $row['start_date'], 'LONG', 0, 1) . '</b></td>';
        if ($row['complete_date'] > 0) {
            $body .= '
        <td class="has-text-centered"><b>' . get_date((int) $row['complete_date'], 'LONG', 0, 1) . '</b></td>';
        } else {
            $body .= "
        <td class='has-text-centered'><b><span class='has-text-danger'>" . _('Not completed') . '</span></b></td>';
        }
        $body .= '
        <td class="has-text-centered"><b>' . ($row['seeder'] === 'yes' ? "<i class='icon-thumbs-up icon has-text-success tooltipper' title='" . _('Yes') . "'></i>" : "<i class='icon-thumbs-down icon has-text-danger tooltipper' title='" . _('No') . "'></i>") . '</b></td>
    </tr>';
    }
    $HTMLOUT .= main_table($body, $heading);
} else {
    $HTMLOUT .= stdmsg('', _('Nothing here :('));
}
if ($count > $snatchedperpage) {
    $HTMLOUT .= $pager['pagerbottom'];
}
$title = _('Snatched Torrents');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
