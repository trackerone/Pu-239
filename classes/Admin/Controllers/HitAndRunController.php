<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Psr\Container\ContainerInterface;

final class HitAndRunController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23T00:00:00Z; tool=codex-admin-convert; rules=2025.10.23
        try {
            // LEGACY BODY START (from admin/hit_and_run.php)
            global $container;
            $container = $this->container;
            /** @var ConfigRepository $config */
            $config = $this->config;
            $db = $container->get(Database::class);

            $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($scriptPath, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $reallyBad = isset($_GET['really_bad']);
            $countSql = $reallyBad
                ? "SELECT COUNT(id) FROM snatched LEFT JOIN users ON users.id = snatched.userid WHERE snatched.finished = 'yes' AND snatched.hit_and_run > 0 AND users.hit_and_run_total > 2"
                : "SELECT COUNT(id) FROM snatched WHERE finished = 'yes' AND hit_and_run > 0";
            $countValue = $db->fetchValue($countSql);
            $count = $countValue !== null ? (int) $countValue : 0;

            $page = isset($_GET['page']) ? (int) $_GET['page'] : 0;
            $perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : 15;
            $link = (string) $config->get('paths.baseurl') . '/staffpanel.php?tool=hit_and_run';
            $pager = pager($perpage, $count, $link);
            $menu_top = $pager['pagertop'];
            $menu_bottom = $pager['pagerbottom'];
            $LIMIT = $pager['limit'];

            $query2 = $reallyBad
                ? "SELECT s.torrentid, s.userid, s.hit_and_run, s.downloaded AS dload, s.uploaded AS uload, s.seedtime, s.start_date, s.complete_date, p.id, p.torrent, p.seeder, u.id, u.avatar, u.offensive_avatar, u.username, u.uploaded AS up, u.downloaded AS down, u.class, u.hit_and_run_total, u.donor, u.warned, u.status, u.leechwarn, u.chatpost, u.pirate, u.king, t.owner, t.name, t.added AS torrent_added, t.seeders AS numseeding, t.leechers AS numleeching FROM snatched AS s LEFT JOIN users AS u ON u.id = s.userid LEFT JOIN peers AS p ON p.torrent = s.torrentid AND p.userid = s.userid LEFT JOIN torrents AS t ON t.id = s.torrentid WHERE finished = 'yes' AND hit_and_run > 0 AND u.hit_and_run_total > 2 ORDER BY userid $LIMIT"
                : "SELECT s.torrentid, s.userid, s.hit_and_run, s.downloaded AS dload, s.uploaded AS uload, s.seedtime, s.start_date, s.complete_date, p.id, p.torrent, p.seeder, u.id, u.avatar, u.username, u.uploaded AS up, u.downloaded AS down, u.class, u.hit_and_run_total, u.donor, u.warned, u.status, u.leechwarn, u.chatpost, u.pirate, u.king, t.owner, t.name, t.added AS torrent_added, t.seeders AS numseeding, t.leechers AS numleeching FROM snatched AS s LEFT JOIN users AS u ON u.id = s.userid LEFT JOIN peers AS p ON p.torrent = s.torrentid AND p.userid = s.userid LEFT JOIN torrents AS t ON t.id = s.torrentid WHERE finished = 'yes' AND hit_and_run > 0 ORDER BY userid $LIMIT";
            $rows = $db->fetchAll($query2);
            $rowCount = count($rows);

            $HTMLOUT = "
            <ul class='level-center bg-06'>
                <li class='is-link margin10'>
                    <a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=hit_and_run'>" . _('show all current hit and runs') . "</a>
                </li>
                <li class='is-link margin10'>
                    <a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=hit_and_run&amp;really_bad=show_them'>" . _('show disabled hit and runs') . "</a>
                </li>
            </ul>
            <h1 class='has-text-centered'>" . (!$reallyBad ? _('Current Hit and Runs who still have a chance') : _('Hit and Runs with no chance')) . '</h1>' . ($count > $perpage ? '<p>' . $menu_top . '</p>' : '');
            $HTMLOUT .= "
        <table class=\"table table-bordered table-striped\">" . ($rowCount > 0 ? '<tr><td class="colhead">' . _('Avatar') . '</td>
        <td class="colhead"><b>' . _('Member') . '</b></td>
        <td class="colhead"><b>' . _('Torrent') . '</b></td>
        <td class="colhead"><b>' . _('Times') . '</b></td>
        <td class="colhead"><b>' . _('Stats') . '</b></td>
        <td class="colhead">' . _('Actions') . '</td>' : '<tr><td><div class="padding20">' . _('no hit and runners at the moment...') . '</div></td>') . '</tr>';

            foreach ($rows as $hit_and_run_arr) {
                $Xbt_Seed = $hit_and_run_arr['seeder'] !== 'yes';
                $Uid_ID = (int) $hit_and_run_arr['userid'];
                $S_date = (int) $hit_and_run_arr['start_date'];
                $T_ID = (int) $hit_and_run_arr['torrentid'];
                $C_Date = (int) $hit_and_run_arr['complete_date'];
                if ($Xbt_Seed) {
                    if ($Uid_ID !== (int) $hit_and_run_arr['owner']) {
                        $site_ratio = $hit_and_run_arr['up'] / ((bool) $config->get('site.ratio_free') ? 1 : (int) $hit_and_run_arr['down']);
                        $torrent_ratio = $hit_and_run_arr['uload'] / ((bool) $config->get('site.ratio_free') ? 1 : (int) $hit_and_run_arr['dload']);
                        $ratio_site = member_ratio((float) $hit_and_run_arr['up'], (float) $hit_and_run_arr['down']);
                        $ratio_torrent = member_ratio((float) $hit_and_run_arr['uload'], (float) $hit_and_run_arr['dload']);
                        $avatar = get_avatar($hit_and_run_arr);
                        $torrent_needed_seed_time = (int) $hit_and_run_arr['seedtime'];
                        switch (true) {
                            case (int) $hit_and_run_arr['class'] <= (int) $config->get('hnr_config.firstclass'):
                                $days_3 = (int) $config->get('hnr_config._3day_first') * 3600;
                                $days_14 = (int) $config->get('hnr_config._14day_first') * 3600;
                                $days_over_14 = (int) $config->get('hnr_config._14day_over_first') * 3600;
                                break;

                            case (int) $hit_and_run_arr['class'] < (int) $config->get('hnr_config.secondclass'):
                                $days_3 = (int) $config->get('hnr_config._3day_second') * 3600;
                                $days_14 = (int) $config->get('hnr_config._14day_second') * 3600;
                                $days_over_14 = (int) $config->get('hnr_config._14day_over_second') * 3600;
                                break;

                            case (int) $hit_and_run_arr['class'] >= (int) $config->get('hnr_config.thirdclass'):
                                $days_3 = (int) $config->get('hnr_config._3day_third') * 3600;
                                $days_14 = (int) $config->get('hnr_config._14day_third') * 3600;
                                $days_over_14 = (int) $config->get('hnr_config._14day_over_third') * 3600;
                                break;

                            default:
                                $days_3 = (int) $config->get('hnr_config._3day_first') * 3600;
                                $days_14 = (int) $config->get('hnr_config._14day_first') * 3600;
                                $days_over_14 = (int) $config->get('hnr_config._14day_over_first') * 3600;
                        }
                        switch (true) {
                            case ($S_date - (int) $hit_and_run_arr['torrent_added']) < (int) $config->get('hnr_config.torrentage1') * 86400:
                                $minus_ratio = $days_3 - $torrent_needed_seed_time;
                                break;

                            case ($S_date - (int) $hit_and_run_arr['torrent_added']) < (int) $config->get('hnr_config.torrentage2') * 86400:
                                $minus_ratio = $days_14 - $torrent_needed_seed_time;
                                break;

                            case ($S_date - (int) $hit_and_run_arr['torrent_added']) >= (int) $config->get('hnr_config.torrentage3') * 86400:
                                $minus_ratio = $days_over_14 - $torrent_needed_seed_time;
                                break;

                            default:
                                $minus_ratio = $days_over_14 - $torrent_needed_seed_time;
                        }
                        $minus_ratio = $minus_ratio < 0 ? 0 : $minus_ratio;
                        $color = $minus_ratio > 0 ? get_ratio_color($minus_ratio) : 'limegreen';
                        $users = $hit_and_run_arr;
                        $users['id'] = $Uid_ID;
                        $HTMLOUT .= '<tr><td class="has-text-centered w-15 mw-150">' . $avatar . '</td>
            <td><a class="is-link" href="' . (string) $config->get('paths.baseurl') . '/userdetails.php?id=' . $Uid_ID . '&amp;completed=1#completed">' . htmlsafechars($users['username']) . '</a>  [ ' . get_user_class_name((int) $hit_and_run_arr['class']) . ' ]
</td>
            <td><a class="is-link" href="details.php?id=' . $T_ID . '&amp;hit=1">' . htmlsafechars($hit_and_run_arr['name']) . '</a><br>
            ' . _('Leechers:') . ' ' . (int) $hit_and_run_arr['numleeching'] . '<br>
            ' . _('Seeders:') . ' ' . (int) $hit_and_run_arr['numseeding'] . '
         </td>
            <td>' . _('Finished DL at:') . ' ' . get_date($C_Date, 'LONG') . '<br>
            ' . _('Stopped seeding at:') . ' ' . get_date((int) $hit_and_run_arr['hit_and_run'], '') . '<br>
            ' . _('Seeded for:') . ' ' . mkprettytime((int) $hit_and_run_arr['seedtime']) . '<br>
            **' . _('Should still seed for') . ': ' . mkprettytime($minus_ratio) . '</td>
            <td>' . _('Uploaded') . ': ' . mksize($hit_and_run_arr['uload']) . '<br>
            ' . ((bool) $config->get('site.ratio_free') ? ' ' : _('Downloaded') . mksize($hit_and_run_arr['dload']) . '<br>') . '
            ' . _('Torrent ratio') . ': <span style="color: ' . get_ratio_color($torrent_ratio) . '">' . $ratio_torrent . '</span><br>
            ' . _('Site ratio') . ': <span style="color: ' . get_ratio_color($site_ratio) . '" title="' . _('includes all bonus and karma stuff') . '">' . $ratio_site . '</span></td>
            <td><a href="messages.php?action=send_message&amp;receiver=' . $Uid_ID . '"><img src="' . (string) $config->get('paths.images_baseurl') . 'pm.gif" alt="PM" title="' . _('Send this user a PM') . '"></a><br>
            <a class="is-link" href="' . (string) $config->get('paths.baseurl') . '/staffpanel.php?tool=shit_list&amp;action2=new&amp;shit_list_id=' . $Uid_ID . '&amp;return_to=staffpanel.php?tool=hit_and_run"><img src="' . (string) $config->get('paths.images_baseurl') . 'smilies/shit.gif" alt="Shit" title="' . _('Shit') . '"></a></td></tr>';
                    }
                }
            }
            $HTMLOUT .= '</table>' . ($count > $perpage ? '<p>' . $menu_bottom . '</p>' : '');
            $title = _('Hit and Runs');
            $breadcrumbs = [
                "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
            // LEGACY BODY END
        } catch (\Throwable $e) {
            error_log('Admin controller error (hit_and_run): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}
