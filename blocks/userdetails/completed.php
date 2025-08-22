<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

global $container, $site_config, $CURUSER;

if ($site_config['hnr_config']['hnr_online'] == 1 && $user['paranoia'] < 2 || $CURUSER['id'] == $id || $CURUSER['class'] >= (UC_MIN + 1)) {
    $completed = $count2 = $dlc = '';
    $fluent = $container->get(Database::class);
    $torrents = $fluent->from('snatched AS s')
                       ->select('t.name')
                       ->select('t.added AS torrent_added')
                       ->select('s.complete_date AS c')
                       ->select('s.downspeed')
                       ->select('s.seedtime')
                       ->select('s.seeder')
                       ->select('s.torrentid AS tid')
                       ->select('s.id')
                       ->select('c.id AS category')
                       ->select('c.image')
                       ->select('c.name AS catname')
                       ->select('p.name AS parent_name')
                       ->select('s.uploaded')
                       ->select('s.downloaded')
                       ->select('s.hit_and_run')
                       ->select('s.mark_of_cain')
                       ->select('s.complete_date')
                       ->select('s.last_action')
                       ->select('t.seeders')
                       ->select('t.leechers')
                       ->select('t.owner')
                       ->select('s.start_date AS st')
                       ->select('s.start_date')
                       ->leftJoin('torrents AS t ON t.id=s.torrentid')
                       ->leftJoin('categories AS c ON c.id=t.category')
                       ->leftJoin('categories AS p ON c.parent_id=p.id')
                       ->where('s.finished = "yes"')
                       ->where('userid = ?', $id)
                       ->where('t.owner != ?', $id)
                       ->orderBy('s.id DESC')
                       ->fetchAll();

    if (count($torrents) > 0) {
        $heading = '
        <tr>
            <th>' . _('Type') . '</th>
            <th>' . _('Name') . '</th>
            <th>' . _('S') . '</th>
            <th>' . _('L') . '</th>
            <th>' . _('UL') . '</th>
            ' . ($site_config['site']['ratio_free'] ? '' : '
            <th>' . _('DL') . '</th>') . '
            <th>' . _('Ratio') . '</th>
            <th>' . _('When Completed') . '</th>
            <th>' . _('Last Action') . '</th>
            <th>' . _('Speed') . '</th>
        </tr>';
        $body = '';
        foreach ($torrents as $a) {
            $What_Id = $a['id'];
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
                case $user['class'] >= $site_config['hnr_config']['secondclass'] && $user['class'] < $site_config['hnr_config']['thirdclass']:
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
                    $days_3 = 0;
                    $days_14 = 0;
                    $days_over_14 = 0;
            }
            $foo = $a['downloaded'] > 0 ? $a['uploaded'] / $a['downloaded'] : 0;
            switch (true) {
                case $site_config['hnr_config']['torrentage1'] * 86400 > ($a['st'] - $a['torrent_added']):
                    $minus_ratio = ($days_3 - $a['seedtime']) - ($foo * 3 * 86400);
                    break;

                case $site_config['hnr_config']['torrentage2'] * 86400 > ($a['st'] - $a['torrent_added']):
                    $minus_ratio = ($days_14 - $a['seedtime']) - ($foo * 2 * 86400);
                    break;

                case $site_config['hnr_config']['torrentage3'] * 86400 <= ($a['st'] - $a['torrent_added']):
                    $minus_ratio = ($days_over_14 - $a['seedtime']) - ($foo * 86400);
                    break;
            }
            $foo = $a['downloaded'] > 0 ? $a['uploaded'] / $a['downloaded'] : 0;
            switch (true) {
                case 7 * 86400 > ($a['st'] - $a['torrent_added']):
                    $minus_ratio = ($days_3 - $a['seedtime']) - ($foo * 3 * 86400);
                    break;

                case 21 * 86400 > ($a['st'] - $a['torrent_added']):
                    $minus_ratio = ($days_14 - $a['seedtime']) - ($foo * 2 * 86400);
                    break;

                case 21 * 86400 <= ($a['st'] - $a['torrent_added']):
                    $minus_ratio = ($days_over_14 - $a['seedtime']) - ($foo * 86400);
                    break;
            }
            $color = (($minus_ratio > 0 && $a['uploaded'] < $a['downloaded']) ? get_ratio_color($minus_ratio) : 'limegreen');
            $minus_ratio = mkprettytime($minus_ratio);
            if ($a['downspeed'] > 0) {
                $dl_speed = ($a['downspeed'] > 0 ? mksize($a['downspeed']) : ($a['leechtime'] > 0 ? mksize($a['downloaded'] / $a['leechtime']) : mksize(0)));
            } else {
                $dl_speed = mksize(($a['downloaded'] / ($a['c'] - $a['st'] + 1)));
            }
            switch (true) {
                case $dl_speed > 600:
                    $dlc = 'red';
                    break;

                case $dl_speed > 300:
                    $dlc = 'orange';
                    break;

                case $dl_speed > 200:
                    $dlc = 'yellow';
                    break;

                case $dl_speed < 100:
                    $dlc = 'Chartreuse';
                    break;
            }
            $checkbox_for_delete = ($CURUSER['class'] >= UC_STAFF ? " [<a href='" . $site_config['paths']['baseurl'] . '/userdetails.php?id=' . $id . '&amp;delete_hit_and_run=' . (int) $What_Id . "'>" . _('Remove') . '</a>]' : '');
            $mark_of_cain = ($a['mark_of_cain'] == 'yes' ? "<img src='{$site_config['paths']['images_baseurl']}moc.gif' width='40px' alt='" . _('Mark Of Cain') . "' title='" . _('The mark of Cain!') . "'>" . $checkbox_for_delete : '');
            $hit_n_run = ($a['hit_and_run'] > 0 ? "<img src='{$site_config['paths']['images_baseurl']}hnr.gif' width='40px' alt='" . _('Hit and run') . "' title='" . _('Hit and run!') . "'>" : '');
            $a['cat'] = $a['parent_name'] . '::' . $a['catname'];
            $caticon = !empty($a['image']) ? "<img height='42px' class='round5 tooltipper' src='{$site_config['paths']['images_baseurl']}caticons/{$CURUSER['categorie_icon']}/{$a['image']}' alt='{$a['cat']}' title='{$a['name']}'>" : $a['cat'];

            $body .= "
            <tr>
                <td style='padding: 5px'>$caticon</td>
                <td>
                    <a class='is-link' href='{$site_config['paths']['baseurl']}/details.php?id=" . (int) $a['tid'] . "&amp;hit=1'><b>" . htmlsafechars((string) $a['name']) . '</b></a>
                    <br><span>  ' . (($CURUSER['class'] >= UC_STAFF || $user['id'] == $CURUSER['id']) ? '' . _('seeded for') . '</span>: ' . mkprettytime($a['seedtime']) . (($minus_ratio != '0:00' && $a['uploaded'] < $a['downloaded']) ? '<br>' . _('should still seed for: ') . '' . $minus_ratio . '&#160;&#160;' : '') . ($a['seeder'] === 'yes' ? "&#160;<span class='has-text-success'> [<b>" . _('seeding') . '</b>]</span>' : $hit_n_run . '&#160;' . $mark_of_cain) : '') . '</td>
                <td>' . (int) $a['seeders'] . '</td>
                <td>' . (int) $a['leechers'] . '</td>
                <td>' . mksize($a['uploaded']) . '</td>
                ' . ($site_config['site']['ratio_free'] ? '' : '
                <td>' . mksize($a['downloaded']) . '</td>') . '
                <td>' . ($a['downloaded'] > 0 ? "<span style='color: " . get_ratio_color($a['uploaded'] / $a['downloaded']) . ";'>" . number_format($a['uploaded'] / $a['downloaded'], 3) . '</span>' : ($a['uploaded'] > 0 ? 'Inf.' : '---')) . '<br></td>
                <td>' . get_date((int) $a['complete_date'], 'DATE') . '</td>
                <td>' . get_date((int) $a['last_action'], 'DATE') . "</td>
                <td><span style='color: $dlc;'>[ " . _fe('Downloaded at: {0}', $dl_speed) . ' ]</span></td>
            </tr>';
        }
        $completed = main_table($body, $heading);
    }

    if (($completed && $CURUSER['class'] >= (UC_MIN + 1)) || ($completed && $user['id'] == $CURUSER['id'])) {
        if (!isset($_GET['completed'])) {
            $table_data .= "
            <tr>
                <td>Completed Torrents</td>
                <td>
                <a id='completed-torrents-hash'></a>
                <fieldset id='completed-torrents' class='header'>
                    <legend class='flipper size_4'><i class='icon-up-open' aria-hidden='true'></i>View Completed Torrents</legend>
                    $completed
                </fieldset>
                </td>
            </tr>";
        }
    }
}
