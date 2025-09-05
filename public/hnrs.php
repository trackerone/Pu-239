<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;
use Pu239\Session;
use Pu239\Snatched;
use Pu239\Torrent;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_onlinetime.php';
require_once CLASS_DIR . 'class_user_options.php';
require_once CLASS_DIR . 'class_user_options_2.php';
$user = check_user_status();
$HTMLOUT = '';
global $container, $site_config;

if (isset($_GET['id']) && $user['class'] >= UC_STAFF) {
    $userid = (int) $_GET['id'];
    $users_class = $container->get(User::class);
    $user_stuff = $users_class->getUserFromId($userid);
} else {
    $userid = $user['id'];
    $user_stuff = $user;
}
$diff = $user_stuff['uploaded'] - $user_stuff['downloaded'];
if ($user['id'] === $userid || has_access($user['class'], UC_ADMINISTRATOR, 'coder')) {
    $bp = $user_stuff['seedbonus'];
} else {
    $bp = 0;
}
$fluent = $container->get(Database::class);
$ratio_fix = $fluent$sql = "SELECT * FROM 'bonus'"; $this->db->fetchAll($sql);;

$completed .= '
<h1>Hit and Runs for: ' . format_username($userid) . '</h1>';
if (count($hnrs) > 0) {
    $heading = '
        <tr>
            <th>' . _('Type') . '</th>
            <th>' . _('Name') . "</th>
            <th class='has-text-centered'>" . _('Seeders') . "</th>
            <th class='has-text-centered'>" . _('Leechers') . "</th>
            <th class='has-text-centered'>" . _('Uploaded') . '</th>
            ' . ($site_config['site']['ratio_free'] ? "
            <th class='has-text-centered'>" . _('Size') . '</th>' : "
            <th class='has-text-centered'>" . _('Downloaded') . '</th>') . "
            <th class='has-text-centered'>" . _('Ratio') . "</th>
            <th class='has-text-centered'>" . _('Completed') . "</th>
            <th class='has-text-centered'>" . _('Last Action') . "</th>
            <th class='has-text-centered'>" . _('Speed') . "</th>
            <th class='has-text-centered'>" . _('Buyout') . '</th>
        </tr>';
    $body = '';
    foreach ($hnrs as $a) {
        $torrent_needed_seed_time = ($a['st'] - $a['torrent_added']);
        switch (true) {
            case $user['class'] <= $site_config['hnr_config']['firstclass']:
                $days_3 = $site_config['hnr_config']['_3day_first'] * 3600;
                $days_14 = $site_config['hnr_config']['_14day_first'] * 3600;
                $days_over_14 = $site_config['hnr_config']['_14day_over_first'] * 3600;
                break;

            case $user['class'] < $site_config['hnr_config']['secondclass']:
                $days_3 = $site_config['hnr_config']['_3day_second'] * 3600;
                $days_14 = $site_config['hnr_config']['_14day_second'] * 3600;
                $days_over_14 = $site_config['hnr_config']['_14day_over_second'] * 3600;
                break;

            case $user['class'] >= $site_config['hnr_config']['thirdclass']:
                $days_3 = $site_config['hnr_config']['_3day_third'] * 3600;
                $days_14 = $site_config['hnr_config']['_14day_third'] * 3600;
                $days_over_14 = $site_config['hnr_config']['_14day_over_third'] * 3600;
                break;

            default:
                $days_3 = $site_config['hnr_config']['_3day_first'] * 3600; //== 1 days
                $days_14 = $site_config['hnr_config']['_14day_first'] * 3600; //== 1 days
                $days_over_14 = $site_config['hnr_config']['_14day_over_first'] * 3600; //== 1 day
        }
        switch (true) {
            case $site_config['hnr_config']['torrentage1'] * 86400 > ($a['st'] - $a['torrent_added']):
                $minus_ratio = ($days_3 - $a['seedtime']);
                break;

            case $site_config['hnr_config']['torrentage2'] * 86400 > ($a['st'] - $a['torrent_added']):
                $minus_ratio = ($days_14 - $a['seedtime']);
                break;

            case $site_config['hnr_config']['torrentage3'] * 86400 <= ($a['st'] - $a['torrent_added']):
                $minus_ratio = ($days_over_14 - $a['seedtime']);
                break;

            default:
                $minus_ratio = ($days_over_14 - $a['seedtime']);
        }
        $color = (($minus_ratio > 0 && $a['uploaded'] < $a['downloaded']) ? get_ratio_color($minus_ratio) : 'limegreen');
        $need_to_seed = mkprettytime($minus_ratio);

        $dl_speed = $a['downloaded'] / ($a['c'] - $a['st'] + 1);
        switch (true) {
            case $dl_speed < 104857:
                $dlc = 'Lime';
                break;
            case $dl_speed < 524288:
                $dlc = 'Chartreuse';
                break;
            case $dl_speed < 1048576:
                $dlc = 'yellow';
                break;
            case $dl_speed < 5242880:
                $dlc = 'orange';
                break;
            case $dl_speed < 10485760:
                $dlc = '#E75480';
                break;
            case $dl_speed > 10485760:
                $dlc = 'red';
                break;
        }

        $dl_speed = mksize($dl_speed);
        $checkbox_for_delete = ($user['class'] >= UC_STAFF && $user['id'] != $userid ? " [<a href='" . $site_config['paths']['baseurl'] . '/userdetails.php?id=' . $userid . '&amp;delete_hit_and_run=' . (int) $a['sid'] . "'>" . _('Remove') . '</a>]' : '');
        $mark_of_cain = ($a['mark_of_cain'] === 'yes' ? "<img src='{$site_config['paths']['images_baseurl']}moc.gif' width='40px' alt='" . _('Mark Of Cain') . "' class='tooltipper' title='" . _('The mark of Cain!') . "'>" . $checkbox_for_delete : '');
        $hit_n_run = ($a['hit_and_run'] > 0 ? "<img src='{$site_config['paths']['images_baseurl']}hnr.gif' width='40px' alt='" . _('Hit and run') . "' class='tooltipper' title='" . _('Hit and run!') . "'>" : '');
        $needs_seed = time() < $a['hit_and_run'] + 86400 ? ' in ' . mkprettytime($a['hit_and_run'] + 86400 - time()) : '';

        if ($bp >= $cost && $cost != 0) {
            $buyout = "
            <form method='post' action='{$site_config['paths']['baseurl']}/hnrs.php' enctype='multipart/form-data' accept-charset='utf-8'>
                <input type='hidden' name='seed' value='{$minus_ratio}'>
                <input type='hidden' name='sid' value='{$a['sid']}'>
                <input type='hidden' name='tid' value='{$a['tid']}'>
                <input type='hidden' name='userid' value='{$userid}'>
                <div class='padding10 has-text-centered'>
                    <input type='submit' value='Seedtime Fix' class='button is-small tooltipper' title='" . _fe('Buyout with {0} bonus points to fix the seedtime for this torrent', $cost) . "'>
                </div>
            </form>";
        } else {
            $buyout = '';
        }

        $a_downloaded = $site_config['site']['ratio_free'] ? $a['size'] : $a['downloaded'];
        $bytes = $a_downloaded - $a['uploaded'];
        if ($diff >= $bytes) {
            $buybytes = "
            <form method='post' action='{$site_config['paths']['baseurl']}/hnrs.php' enctype='multipart/form-data' accept-charset='utf-8'>
                <input type='hidden' name='bytes' value='{$bytes}'>
                <input type='hidden' name='sid' value='{$a['sid']}'>
                <input type='hidden' name='tid' value='{$a['tid']}'>
                <input type='hidden' name='userid' value='{$userid}'>
                <div class='padding10 has-text-centered'>
                    <input type='submit' value='Ratio Fix' class='button is-small tooltipper' title='" . _fe('Buyout with {0} upload credit to fix the ratio for this torrent', mksize($bytes)) . "'>
                </div>
            </form>";
        } else {
            $buybytes = '';
        }

        $or = !empty($buyout) && !empty($buybytes) ? 'or' : '';
        $sucks = empty($buyout) ? _fe('Seed for {0}', $need_to_seed) : _fe('or<br>Seed for {0}', $need_to_seed);
        $a['cat'] = $a['parent_name'] . ' :: ' . $a['catname'];
        $caticon = !empty($a['image']) ? "<img height='42px' class='round5 tooltipper' src='{$site_config['paths']['images_baseurl']}caticons/{$user['categorie_icon']}/{$a['image']}' alt='{$a['cat']}' title='{$a['name']}'>" : $a['cat'];
        $body .= "
        <tr>
            <td class='padding5'>$caticon</td>
            <td><a class='is-link' href='details.php?id=" . (int) $a['tid'] . "&amp;hit=1'><b>" . format_comment($a['name']) . "</b></a>
                <br><span style='color: {$color};'>  " . (($user['class'] >= UC_STAFF || $user['id'] == $userid) ? _('seeded for') . '</span>: ' . mkprettytime($a['seedtime']) . (($need_to_seed != '0:00') ? '<br>' . _('should still seed for: ') . '' . $need_to_seed . '&#160;&#160;' : '') . ($a['seeder'] === 'yes' ? "&#160;<span class='has-text-success'> [<b>" . _('seeding') . '</b>]</span>' : $hit_n_run . '&#160;' . $mark_of_cain . $needs_seed) : '') . "
            </td>
            <td class='has-text-centered'>" . (int) $a['seeders'] . "</td>
            <td class='has-text-centered'>" . (int) $a['leechers'] . "</td>
            <td class='has-text-centered'>" . mksize($a['uploaded']) . '</td>
            ' . ($site_config['site']['ratio_free'] ? "<td class='has-text-centered'>" . mksize($a['size']) . '</td>' : "<td class='has-text-centered'>" . mksize($a['downloaded']) . '</td>') . "
            <td class='has-text-centered'>" . ($a['downloaded'] > 0 ? "<span style='color: " . get_ratio_color($a['uploaded'] / $a['downloaded']) . ";'>" . number_format($a['uploaded'] / $a['downloaded'], 3) . '</span>' : ($a['uploaded'] > 0 ? 'Inf.' : '---')) . "<br></td>
            <td class='has-text-centered'>" . get_date((int) $a['complete_date'], 'DATE') . "</td>
            <td class='has-text-centered'>" . get_date((int) $a['last_action'], 'DATE') . "</td>
            <td class='has-text-centered'><span style='color: $dlc;'>" . _(' DLed at: ') . "<br>{$dl_speed}ps</span></td>
            <td class='has-text-centered'>{$buyout}{$buybytes}{$sucks}</td>
        </tr>";
    }
    $completed .= main_table($body, $heading);
} else {
    $completed = main_div(_fe('{0} has no Hit and Runs!', format_username($userid)), '', 'padding20 has-text-centered');
}

$title = _('HnRs');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/userdetails.php?id={$userid}'>" . _('User Details') . '</a>',
    "<a href='{$site_config['paths']['baseurl']}/usercp.php?id={$userid}'>" . _('User CP') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($completed) . stdfoot();
