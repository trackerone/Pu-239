<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:32:40Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use PU239\Config\ConfigRepository;
use Pu239\Database;

final class HitAndRunHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:32:40Z via codex handler conversion
        try {
            $container = $GLOBALS['container'] ?? null;
            if ($container === null) {
                throw new \RuntimeException('Global container not initialized');
            }

            if (defined('ADMIN_DIR') && strpos((string) ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escaper = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escaper($_SERVER['PHP_SELF'] ?? '');
            $baseurl = (string) $config->get('paths.baseurl');
            $baseurlEscaped = $escaper($baseurl);

            $reallyBad = isset($_GET['really_bad']);

            if ($reallyBad) {
                $countRow = $db->fetch(
                    "SELECT COUNT(s.id) AS count
                       FROM snatched AS s
                       LEFT JOIN users AS u ON u.id = s.userid
                      WHERE s.finished = 'yes' AND s.hit_and_run > 0 AND u.hit_and_run_total > 2"
                );
            } else {
                $countRow = $db->fetch(
                    "SELECT COUNT(id) AS count
                       FROM snatched
                      WHERE finished = 'yes' AND hit_and_run > 0"
                );
            }

            $count = (int) ($countRow['count'] ?? 0);
            $perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : 15;
            $link = $baseurl . '/staffpanel.php?tool=hit_and_run';
            $pager = pager($perpage, $count, $link);
            $menu_top = $pager['pagertop'];
            $menu_bottom = $pager['pagerbottom'];
            $limitClause = $pager['limit'];

            if ($reallyBad) {
                $rows = $db->fetchAll(
                    "SELECT s.torrentid,
                            s.userid,
                            s.hit_and_run,
                            s.downloaded AS dload,
                            s.uploaded AS uload,
                            s.seedtime,
                            s.start_date,
                            s.complete_date,
                            p.id AS peer_id,
                            p.torrent,
                            p.seeder,
                            u.id AS user_id,
                            u.avatar,
                            u.offensive_avatar,
                            u.username,
                            u.uploaded AS up,
                            u.downloaded AS down,
                            u.class,
                            u.hit_and_run_total,
                            u.donor,
                            u.warned,
                            u.status,
                            u.leechwarn,
                            u.chatpost,
                            u.pirate,
                            u.king,
                            t.owner,
                            t.name,
                            t.added AS torrent_added,
                            t.seeders AS numseeding,
                            t.leechers AS numleeching
                       FROM snatched AS s
                       LEFT JOIN users AS u ON u.id = s.userid
                       LEFT JOIN peers AS p ON p.torrent = s.torrentid AND p.userid = s.userid
                       LEFT JOIN torrents AS t ON t.id = s.torrentid
                      WHERE s.finished = 'yes' AND s.hit_and_run > 0 AND u.hit_and_run_total > 2
                      ORDER BY s.userid $limitClause"
                );
            } else {
                $rows = $db->fetchAll(
                    "SELECT s.torrentid,
                            s.userid,
                            s.hit_and_run,
                            s.downloaded AS dload,
                            s.uploaded AS uload,
                            s.seedtime,
                            s.start_date,
                            s.complete_date,
                            p.id AS peer_id,
                            p.torrent,
                            p.seeder,
                            u.id AS user_id,
                            u.avatar,
                            u.username,
                            u.uploaded AS up,
                            u.downloaded AS down,
                            u.class,
                            u.hit_and_run_total,
                            u.donor,
                            u.warned,
                            u.status,
                            u.leechwarn,
                            u.chatpost,
                            u.pirate,
                            u.king,
                            t.owner,
                            t.name,
                            t.added AS torrent_added,
                            t.seeders AS numseeding,
                            t.leechers AS numleeching
                       FROM snatched AS s
                       LEFT JOIN users AS u ON u.id = s.userid
                       LEFT JOIN peers AS p ON p.torrent = s.torrentid AND p.userid = s.userid
                       LEFT JOIN torrents AS t ON t.id = s.torrentid
                      WHERE s.finished = 'yes' AND s.hit_and_run > 0
                      ORDER BY s.userid $limitClause"
                );
            }

            $HTMLOUT = "
            <ul class='level-center bg-06'>
                <li class='is-link margin10'>
                    <a href='{$baseurlEscaped}/staffpanel.php?tool=hit_and_run'>" . _('show all current hit and runs') . "</a>
                </li>
                <li class='is-link margin10'>
                    <a href='{$baseurlEscaped}/staffpanel.php?tool=hit_and_run&amp;really_bad=show_them'>" . _('show disabled hit and runs') . "</a>
                </li>
            </ul>
            <h1 class='has-text-centered'>" . ($reallyBad ? _('Hit and Runs with no chance') : _('Current Hit and Runs who still have a chance')) . '</h1>';

            if ($count > $perpage) {
                $HTMLOUT .= '<p>' . $menu_top . '</p>';
            }

            $HTMLOUT .= "
        <table class=\"table table-bordered table-striped\">";

            if (count($rows) === 0) {
                $HTMLOUT .= "<tr><td><div class='padding20'>" . _('no hit and runners at the moment...') . '</div></td></tr>';
            } else {
                $HTMLOUT .= "<tr><td class=\"colhead\">" . _('Avatar') . "</td>
        <td class=\"colhead\"><b>" . _('Member') . "</b></td>
        <td class=\"colhead\"><b>" . _('Torrent') . "</b></td>
        <td class=\"colhead\"><b>" . _('Times') . "</b></td>
        <td class=\"colhead\"><b>" . _('Stats') . "</b></td>
        <td class=\"colhead\">" . _('Actions') . '</td></tr>';

                foreach ($rows as $row) {
                    $isSeeder = ($row['seeder'] ?? 'no') !== 'yes';
                    $userId = (int) ($row['userid'] ?? 0);
                    $startDate = (int) ($row['start_date'] ?? 0);
                    $torrentId = (int) ($row['torrentid'] ?? 0);
                    $completeDate = (int) ($row['complete_date'] ?? 0);

                    if (!$isSeeder) {
                        continue;
                    }

                    if ($userId === (int) ($row['owner'] ?? 0)) {
                        continue;
                    }

                    $siteRatioDivisor = (bool) $config->get('site.ratio_free') ? 1 : max(1, (int) ($row['down'] ?? 0));
                    $torrentRatioDivisor = (bool) $config->get('site.ratio_free') ? 1 : max(1, (int) ($row['dload'] ?? 0));
                    $site_ratio = (float) ($row['up'] ?? 0) / $siteRatioDivisor;
                    $torrent_ratio = (float) ($row['uload'] ?? 0) / $torrentRatioDivisor;
                    $ratio_site = member_ratio((float) ($row['up'] ?? 0), (float) ($row['down'] ?? 0));
                    $ratio_torrent = member_ratio((float) ($row['uload'] ?? 0), (float) ($row['dload'] ?? 0));
                    $avatar = get_avatar($row);
                    $torrent_needed_seed_time = (int) ($row['seedtime'] ?? 0);

                    switch (true) {
                        case ((int) ($row['class'] ?? 0)) <= (int) $config->get('hnr_config.firstclass'):
                            $days_3 = (int) $config->get('hnr_config._3day_first') * 3600;
                            $days_14 = (int) $config->get('hnr_config._14day_first') * 3600;
                            $days_over_14 = (int) $config->get('hnr_config._14day_over_first') * 3600;
                            break;
                        case ((int) ($row['class'] ?? 0)) < (int) $config->get('hnr_config.secondclass'):
                            $days_3 = (int) $config->get('hnr_config._3day_second') * 3600;
                            $days_14 = (int) $config->get('hnr_config._14day_second') * 3600;
                            $days_over_14 = (int) $config->get('hnr_config._14day_over_second') * 3600;
                            break;
                        case ((int) ($row['class'] ?? 0)) >= (int) $config->get('hnr_config.thirdclass'):
                            $days_3 = (int) $config->get('hnr_config._3day_third') * 3600;
                            $days_14 = (int) $config->get('hnr_config._14day_third') * 3600;
                            $days_over_14 = (int) $config->get('hnr_config._14day_over_third') * 3600;
                            break;
                        default:
                            $days_3 = (int) $config->get('hnr_config._3day_first') * 3600;
                            $days_14 = (int) $config->get('hnr_config._14day_first') * 3600;
                            $days_over_14 = (int) $config->get('hnr_config._14day_over_first') * 3600;
                            break;
                    }

                    switch (true) {
                        case ($startDate - (int) ($row['torrent_added'] ?? 0)) < (int) $config->get('hnr_config.torrentage1') * 86400:
                            $minus_ratio = $days_3 - $torrent_needed_seed_time;
                            break;
                        case ($startDate - (int) ($row['torrent_added'] ?? 0)) < (int) $config->get('hnr_config.torrentage2') * 86400:
                            $minus_ratio = $days_14 - $torrent_needed_seed_time;
                            break;
                        case ($startDate - (int) ($row['torrent_added'] ?? 0)) >= (int) $config->get('hnr_config.torrentage3') * 86400:
                            $minus_ratio = $days_over_14 - $torrent_needed_seed_time;
                            break;
                        default:
                            $minus_ratio = $days_over_14 - $torrent_needed_seed_time;
                            break;
                    }

                    $minus_ratio = $minus_ratio < 0 ? 0 : $minus_ratio;
                    $HTMLOUT .= '<tr><td class="has-text-centered w-15 mw-150">' . $avatar . '</td>
            <td><a class="is-link" href="' . $baseurl . '/userdetails.php?id=' . $userId . '&amp;completed=1#completed">' . htmlsafechars((string) ($row['username'] ?? '')) . '</a>  [ ' . get_user_class_name((int) ($row['class'] ?? 0)) . ' ]
</td>
            <td><a class="is-link" href="details.php?id=' . $torrentId . '&amp;hit=1">' . htmlsafechars((string) ($row['name'] ?? '')) . '</a><br>
            ' . _('Leechers:') . ' ' . (int) ($row['numleeching'] ?? 0) . '<br>
            ' . _('Seeders:') . ' ' . (int) ($row['numseeding'] ?? 0) . '
         </td>
            <td>' . _('Finished DL at:') . ' ' . get_date($completeDate, 'LONG') . '<br>
            ' . _('Stopped seeding at:') . ' ' . get_date((int) ($row['hit_and_run'] ?? 0), '') . '<br>
            ' . _('Seeded for:') . ' ' . mkprettytime((int) ($row['seedtime'] ?? 0)) . '<br>
            **' . _('Should still seed for') . ': ' . mkprettytime($minus_ratio) . '</td>
            <td>' . _('Uploaded') . ': ' . mksize((int) ($row['uload'] ?? 0)) . '<br>
            ' . ((bool) $config->get('site.ratio_free') ? ' ' : _('Downloaded') . mksize((int) ($row['dload'] ?? 0)) . '<br>') . '
            ' . _('Torrent ratio') . ': <span style="color: " ' . get_ratio_color($torrent_ratio) . '">' . $ratio_torrent . '</span><br>
            ' . _('Site ratio') . ': <span style="color: "' . get_ratio_color($site_ratio) . '" title="' . _('includes all bonus and karma stuff') . '">' . $ratio_site . '</font></td>
            <td><a href="messages.php?action=send_message&amp;receiver=' . $userId . '"><img src="' . (string) $config->get('paths.images_baseurl') . 'pm.gif" alt="PM" title="' . _('Send this user a PM') . '"></a><br>
            <a class="is-link" href="' . $baseurl . '/staffpanel.php?tool=shit_list&amp;action2=new&amp;shit_list_id=' . $userId . '&amp;return_to=staffpanel.php?tool=hit_and_run"><img src="' . (string) $config->get('paths.images_baseurl') . 'smilies/shit.gif" alt="Shit" title="' . _('Shit') . '"></a></td></tr>';
                }
            }

            $HTMLOUT .= '</table>';

            if ($count > $perpage) {
                $HTMLOUT .= '<p>' . $menu_bottom . '</p>';
            }

            $title = _('Hit and Runs');
            $breadcrumbs = [
                "<a href='{$baseurlEscaped}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
