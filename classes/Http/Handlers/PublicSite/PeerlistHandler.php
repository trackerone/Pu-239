<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T21:32:58Z via handler-convert offset=250 batch=5

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Torrent;

use function dirname;
use function htmlspecialchars;
use function is_numeric;
use function preg_replace;
use function sprintf;

final class PeerlistHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T21:32:58Z via handler-convert offset=250 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            $fluent = $db;
            /** @var Torrent $torrentService */
            $torrentService = $container->get(Torrent::class);

            $user = check_user_status();
            $id = (int) ($_GET['id'] ?? 0);
            if (!is_numeric($id) || $id < 1) {
                stderr(_('Error'), _('Invalid ID'));
            }

            $ratioFree = (bool) $config->get('site.ratio_free');
            $baseUrl = (string) $config->get('paths.baseurl');

            $torrent = $torrentService->get($id);
            if (empty($torrent)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            $peers = $fluent->from('peers AS p')
                ->select('t.anonymous AS tanonymous')
                ->select('t.owner')
                ->select('p.seeder')
                ->select('p.finishedat')
                ->select('p.downloadoffset')
                ->select('p.uploadoffset')
                ->select('INET6_NTOA(p.ip) AS ip')
                ->select('p.port')
                ->select('p.uploaded')
                ->select('p.downloaded')
                ->select('p.to_go')
                ->select('p.started AS st')
                ->select('p.connectable')
                ->select('p.agent')
                ->select('p.last_action AS la')
                ->select('p.userid')
                ->select('p.peer_id')
                ->select('u.username')
                ->select('u.anonymous_until')
                ->select('u.paranoia')
                ->innerJoin('torrents AS t ON t.id = p.torrent')
                ->leftJoin('users AS u ON u.id = p.userid')
                ->where('p.torrent = ?', $id)
                ->fetchAll();

            if ($peers === []) {
                stderr(_('Error'), _('No downloader/uploader data available!'));
            }

            $downloaders = [];
            $seeders = [];
            foreach ($peers as $peer) {
                if (($peer['seeder'] ?? '') === 'yes') {
                    $seeders[] = $peer;
                } else {
                    $downloaders[] = $peer;
                }
            }

            $dltable = static function (string $name, array $arr, array $torrentRow, array $userRow) use ($ratioFree): string {
                if ($arr === []) {
                    return main_div('<div><b>' . _fe('No {0} data available', $name) . '</b></div>', '', 'padding20 has-text-centered');
                }

                $heading = "
        <tr>
            <th>" . _('User/IP') . "</th>
            <th>" . _('Connectable') . "</th>
            <th>" . _('Uploaded') . "</th>
            <th>" . _('Rate') . "</th>";
                if (!$ratioFree) {
                    $heading .= "
            <th>" . _('Downloaded') . "</th>
            <th>" . _('Rate') . "</th>";
                }
                $heading .= "
            <th>" . _('Ratio') . "</th>
            <th>" . _('Complete') . "</th>
            <th>" . _('Connected') . "</th>
            <th>" . _('Idle') . "</th>
            <th>" . _('Client') . "</th>
        </tr>";

                $now = TIME_NOW;
                $mod = ($userRow['class'] ?? 0) >= UC_STAFF;
                $body = '';
                foreach ($arr as $e) {
                    $body .= "
        <tr>";
                    if (!empty($e['username'])) {
                        $shouldMask = (
                            ($e['tanonymous'] === '1' && $e['owner'] === $e['userid'])
                            || ($e['anonymous_until'] ?? 0) > TIME_NOW
                            || ($e['paranoia'] ?? 0) >= 2
                        ) && ($userRow['id'] ?? 0) !== ($e['userid'] ?? 0) && ($userRow['class'] ?? 0) < UC_STAFF;
                        if ($shouldMask) {
                            $body .= "
            <td><b>" . get_anonymous_name() . '</b></td>';
                        } else {
                            $body .= "
            <td>" . format_username((int) $e['userid']) . '</td>';
                        }
                    } else {
                        $body .= "
            <td>" . ($mod ? ($e['ip'] ?? '') : preg_replace('/\.\d+$/', '.xxx', $e['ip'] ?? '')) . '</td>';
                    }
                    $secs = max(1, ($now - ($e['st'] ?? 0)) - ($now - ($e['la'] ?? 0)));
                    $body .= "
            <td>" . (($e['connectable'] ?? '') === 'yes' ? _('Yes') : "<span class='has-text-danger'>" . _('No') . '</span>') . "</td>";
                    $body .= "
            <td>" . mksize((int) ($e['uploaded'] ?? 0)) . '</td>';
                    $body .= "
            <td><span style='white-space: nowrap;'>" . mksize((($e['uploaded'] ?? 0) - ($e['uploadoffset'] ?? 0)) / $secs) . '/s</span></td>';
                    if (!$ratioFree) {
                        $body .= "
            <td>" . mksize((int) ($e['downloaded'] ?? 0)) . '</td>';
                        if (($e['seeder'] ?? '') === 'no') {
                            $body .= "
            <td><span style='white-space: nowrap;'>" . mksize((($e['downloaded'] ?? 0) - ($e['downloadoffset'] ?? 0)) / $secs) . '/s</span></td>';
                        } else {
                            $body .= "
            <td><span style='white-space: nowrap;'>" . mksize((($e['downloaded'] ?? 0) - ($e['downloadoffset'] ?? 0)) / max(1, ($e['finishedat'] ?? 0) - ($e['st'] ?? 0))) . '/s</span></td>';
                        }
                    }
                    $body .= "
            <td>" . member_ratio((int) ($e['uploaded'] ?? 0), (int) ($e['downloaded'] ?? 0)) . '</td>';
                    $body .= "
            <td>" . sprintf('%.2f%%', 100 * (1 - (($e['to_go'] ?? 0) / $torrentRow['size']))) . '</td>';
                    $body .= "
            <td>" . mkprettytime($now - ($e['st'] ?? 0)) . '</td>';
                    $body .= "
            <td>" . mkprettytime($now - ($e['la'] ?? 0)) . '</td>';
                    $body .= "
            <td>" . htmlsafechars(getagent($e['agent'] ?? '', $e['peer_id'] ?? '')) . '</td>';
                    $body .= '
        </tr>';
                }

                return "<h3 class='has-text-centered'>" . count($arr) . " {$name}" . plural(count($arr)) . '</h3>' . main_table($body, $heading);
            };

            $seedSort = static function (array $a, array $b): int {
                $x = $a['uploaded'] ?? 0;
                $y = $b['uploaded'] ?? 0;
                if ($x === $y) {
                    return 0;
                }

                return $x < $y ? 1 : -1;
            };

            $leechSort = static function (array $a, array $b) use ($seedSort): int {
                if (isset($_GET['usort'])) {
                    return $seedSort($a, $b);
                }

                $x = $a['to_go'] ?? 0;
                $y = $b['to_go'] ?? 0;
                if ($x === $y) {
                    return 0;
                }

                return $x < $y ? -1 : 1;
            };

            usort($seeders, $seedSort);
            usort($downloaders, $leechSort);

            $self = htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $HTMLOUT = "
    <h1 class='has-text-centered'>" . _fe('Peerlist for {0}{1}{2}', "<a href='{$baseUrl}/details.php?id=$id'>", format_comment($torrent['name']), '</a>') . '</h1>';
            $HTMLOUT .= $dltable(_('Seeder') . "<a id='seeders'></a>", $seeders, $torrent, $user);
            $HTMLOUT .= '<br>' . $dltable(_('Leecher') . "<a id='leechers'></a>", $downloaders, $torrent, $user);

            $title = _('Peerlist');
            $breadcrumbs = [
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
