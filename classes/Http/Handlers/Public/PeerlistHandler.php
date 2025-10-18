<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:40:29Z via handler-convert offset=210 size=5

namespace PU239\Http\Handlers\Public;

use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Torrent;
use RuntimeException;
use PDO;

final class PeerlistHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:40:29Z via handler-convert offset=210 size=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Torrent $torrents */
            $torrents = $container->get(Torrent::class);

            $ratioFree = (bool) $config->get('site.ratio_free');
            $baseUrl = (string) $config->get('paths.baseurl');

            $user = check_user_status();
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!is_valid_id($id)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            $torrent = $torrents->get($id);
            if (empty($torrent)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            $peers = $db->toArray(
                'SELECT t.anonymous AS tanonymous,
                        t.owner,
                        p.seeder,
                        p.finishedat,
                        p.downloadoffset,
                        p.uploadoffset,
                        INET6_NTOA(p.ip) AS ip,
                        p.port,
                        p.uploaded,
                        p.downloaded,
                        p.to_go,
                        p.started AS st,
                        p.connectable,
                        p.agent,
                        p.last_action AS la,
                        p.userid,
                        p.peer_id,
                        u.username,
                        u.anonymous_until,
                        u.paranoia
                    FROM peers AS p
                    INNER JOIN torrents AS t ON t.id = p.torrent
                    LEFT JOIN users AS u ON u.id = p.userid
                    WHERE p.torrent = :id',
                [
                    'id' => [$id, PDO::PARAM_INT],
                ],
            );

            if ($peers === []) {
                stderr(_('Error'), _('No downloader/uploader data available!'));
            }

            $seeders = [];
            $downloaders = [];
            foreach ($peers as $row) {
                if (($row['seeder'] ?? '') === 'yes') {
                    $seeders[] = $row;
                } else {
                    $downloaders[] = $row;
                }
            }

            $useSeederSort = isset($_GET['usort']);
            usort($seeders, [self::class, 'compareSeeders']);
            usort($downloaders, $useSeederSort ? [self::class, 'compareSeeders'] : [self::class, 'compareLeechers']);

            $htmlOut = '';
            $htmlOut .= "    <h1 class='has-text-centered'>" . _fe('Peerlist for {0}{1}{2}', "<a href='{$baseUrl}/details.php?id=$id'>", format_comment($torrent['name']), '</a>') . '</h1>';
            $htmlOut .= $this->renderPeerTable(_('Seeder') . "<a id='seeders'></a>", $seeders, $torrent, $user, $ratioFree);
            $htmlOut .= '<br>' . $this->renderPeerTable(_('Leecher') . "<a id='leechers'></a>", $downloaders, $torrent, $user, $ratioFree);

            $title = _('Peerlist');
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed>             $torrent
     * @param array<string, mixed>             $user
     */
    private function renderPeerTable(string $name, array $rows, array $torrent, array $user, bool $ratioFree): string
    {
        if ($rows === []) {
            return main_div('<div><b>' . _fe('No {0} data available', $name) . '</b></div>', '', 'padding20 has-text-centered');
        }

        $heading = '
        <tr>
            <th>' . _('User/IP') . '</th>
            <th>' . _('Connectable') . '</th>
            <th>' . _('Uploaded') . '</th>
            <th>' . _('Rate') . '</th>' . ($ratioFree ? '' : '
            <th>' . _('Downloaded') . '</th>') . ($ratioFree ? '' : '
            <th>' . _('Rate') . '</th>') . '
            <th>' . _('Ratio') . '</th>
            <th>' . _('Complete') . '</th>
            <th>' . _('Connected') . '</th>
            <th>' . _('Idle') . '</th>
            <th>' . _('Client') . '</th>
        </tr>';

        $now = TIME_NOW;
        $moderator = ($user['class'] ?? 0) >= UC_STAFF;
        $body = '';

        foreach ($rows as $entry) {
            $body .= "        <tr>";
            if (!empty($entry['username'])) {
                $showAnon = (
                    ((($entry['tanonymous'] ?? '') === '1' && ($entry['owner'] ?? 0) === ($entry['userid'] ?? 0))
                        || ($entry['anonymous_until'] ?? 0) > TIME_NOW
                        || ($entry['paranoia'] ?? 0) >= 2)
                    && ($user['id'] ?? 0) !== (int) ($entry['userid'] ?? 0)
                    && ($user['class'] ?? 0) < UC_STAFF
                );
                if ($showAnon) {
                    $body .= "            <td><b>" . get_anonymous_name() . '</b></td>';
                } else {
                    $body .= '            <td>' . format_username((int) $entry['userid']) . '</td>';
                }
            } else {
                $ipValue = (string) ($entry['ip'] ?? '');
                $body .= '            <td>' . ($moderator ? $ipValue : preg_replace('/\.\d+$/', '.xxx', $ipValue)) . '</td>';
            }

            $seconds = max(1, ($now - (int) ($entry['st'] ?? 0)) - ($now - (int) ($entry['la'] ?? 0)));
            $body .= '<td>' . (($entry['connectable'] ?? '') === 'yes' ? _('Yes') : "<span class='has-text-danger'>" . _('No') . '</span>') . "</td>\n";
            $body .= '<td>' . mksize((int) ($entry['uploaded'] ?? 0)) . "</td>\n";
            $body .= '<td><span style="white-space: nowrap;">' . mksize(((int) ($entry['uploaded'] ?? 0) - (int) ($entry['uploadoffset'] ?? 0)) / $seconds) . "/s</span></td>\n";

            if (!$ratioFree) {
                $body .= '<td>' . mksize((int) ($entry['downloaded'] ?? 0)) . "</td>\n";
            }

            if (($entry['seeder'] ?? '') === 'no') {
                if (!$ratioFree) {
                    $body .= '<td><span style="white-space: nowrap;">' . mksize(((int) ($entry['downloaded'] ?? 0) - (int) ($entry['downloadoffset'] ?? 0)) / $seconds) . "/s</span></td>\n";
                }
            } else {
                if (!$ratioFree) {
                    $finishedAt = (int) ($entry['finishedat'] ?? 0);
                    $startedAt = (int) ($entry['st'] ?? 0);
                    $duration = max(1, $finishedAt - $startedAt);
                    $body .= '<td><span style="white-space: nowrap;">' . mksize(((int) ($entry['downloaded'] ?? 0) - (int) ($entry['downloadoffset'] ?? 0)) / $duration) . "/s</span></td>\n";
                }
            }

            $body .= '<td>' . member_ratio((float) ($entry['uploaded'] ?? 0), (float) ($entry['downloaded'] ?? 0)) . "</td>\n";
            $body .= '<td>' . sprintf('%.2f%%', 100 * (1 - ((float) ($entry['to_go'] ?? 0) / (float) $torrent['size']))) . "</td>\n";
            $body .= '<td>' . mkprettytime($now - (int) ($entry['st'] ?? 0)) . "</td>\n";
            $body .= '<td>' . mkprettytime($now - (int) ($entry['la'] ?? 0)) . "</td>\n";
            $body .= '<td>' . htmlsafechars(getagent((string) ($entry['agent'] ?? ''), (string) ($entry['peer_id'] ?? ''))) . "</td>\n";
            $body .= '</tr>';
        }

        $table = main_table($body, $heading);

        return "<h3 class='has-text-centered'>" . count($rows) . " {$name}" . plural(count($rows)) . '</h3>' . $table;
    }

    /** @param array<string, mixed> $a */
    private static function compareSeeders(array $a, array $b): int
    {
        $x = (int) ($a['uploaded'] ?? 0);
        $y = (int) ($b['uploaded'] ?? 0);
        return $y <=> $x;
    }

    /** @param array<string, mixed> $a */
    private static function compareLeechers(array $a, array $b): int
    {
        $x = (float) ($a['to_go'] ?? 0);
        $y = (float) ($b['to_go'] ?? 0);
        return $x <=> $y;
    }
}
