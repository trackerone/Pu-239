<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:25:00Z via handler-convert offset=180 size=5

namespace PU239\Http\Handlers\Public;

use PDO;
use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;
use Pu239\Torrent;
use RuntimeException;

final class SnatchesHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:25:00Z via handler-convert offset=180 size=5
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
            /** @var Torrent $torrentRepository */
            $torrentRepository = $container->get(Torrent::class);
            /** @var Session $session */
            $session = $container->get(Session::class);

            $user = check_user_status();

            $baseUrl = (string) $config->get('paths.baseurl');
            $ratioFree = (bool) $config->get('site.ratio_free');

            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($id <= 0) {
                $session->set('is-warning', 'Invalid Information');
                header("Location: {$baseUrl}/index.php");
                app_halt('Exit called');
            }

            if (!is_valid_id($id)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            $count = (int) ($db->fetchValue(
                'SELECT COUNT(s.id) AS count
                    FROM snatched AS s
                    LEFT JOIN torrents AS t ON s.torrentid = t.id
                    WHERE s.torrentid = :id AND t.owner != s.userid AND s.to_go = 0',
                ['id' => $id],
            ) ?? 0);

            $perpage = 25;
            $pager = pager($perpage, $count, $baseUrl . "/snatches.php?id={$id}&");

            if ($count === 0) {
                stderr(
                    _('No Snatches'),
                    _fe(
                        'It appears that there are currently no snatches for this {0}torrent.{1}',
                        "<a href='{$baseUrl}/details.php?id={$id}'>",
                        '</a>',
                    ),
                );
            }

            $name = $torrentRepository->get_items(['name'], $id);

            $HTMLOUT = "
    <h1 class='has-text-centered'>" . _('Snatches for torrent') . "</h1>
    <h3 class='has-text-centered'><a href='{$baseUrl}/details.php?id={$id}'>" . htmlsafechars((string) $name) . "</a></h3>
    <h3 class='has-text-centered'>Currently {$count} snatch" . ($count === 1 ? '' : 'es') . '</h3>';

            if ($count > $perpage) {
                $HTMLOUT .= $pager['pagertop'];
            }

            $header = "
        <tr>
            <th class='has-text-left'>" . _('Username') . "</th>
            <th class='has-text-right'>" . _('Uploaded') . "</th>
            <th class='has-text-right'>" . _('Upspeed') . '</th>' .
                ($ratioFree ? '' : "
            <th class='has-text-right'>" . _('Downloaded') . "</th>
            <th class='has-text-right'>" . _('Downspeed') . "</th>") . "
            <th class='has-text-right'>" . _('Ratio') . "</th>
            <th class='has-text-right'>" . _('Completed') . "</th>
            <th class='has-text-right'>" . _('Seed time') . "</th>
            <th class='has-text-right'>" . _('Leech time') . "</th>
            <th class='has-text-centered'>" . _('Last action') . "</th>
            <th class='has-text-centered'>" . _('Completed at') . "</th>
            <th class='has-text-centered'>" . _('Announced') . "</th>
        </tr>";

            $snatches = $db->fetchAll(
                'SELECT s.*, u.paranoia, t.anonymous, t.size, t.owner
                    FROM snatched AS s
                    LEFT JOIN torrents AS t ON s.torrentid = t.id
                    LEFT JOIN users AS u ON s.userid = u.id
                    WHERE s.torrentid = :id AND t.owner != s.userid AND s.to_go = 0
                    LIMIT :limit OFFSET :offset',
                [
                    'id' => $id,
                    'limit' => [$pager['pdo']['limit'], PDO::PARAM_INT],
                    'offset' => [$pager['pdo']['offset'], PDO::PARAM_INT],
                ],
            );

            $body = '';
            foreach ($snatches as $row) {
                $upspeed = ($row['upspeed'] > 0
                    ? mksize((int) $row['upspeed'])
                    : ($row['seedtime'] > 0 ? mksize($row['uploaded'] / ($row['seedtime'] + $row['leechtime'])) : mksize(0)));
                $downspeed = ($row['downspeed'] > 0
                    ? mksize((int) $row['downspeed'])
                    : ($row['leechtime'] > 0 ? mksize($row['downloaded'] / $row['leechtime']) : mksize(0)));
                $ratio = ($row['downloaded'] > 0
                    ? number_format($row['uploaded'] / $row['downloaded'], 3)
                    : ($row['uploaded'] > 0 ? 'Inf.' : '---'));
                $completed = sprintf('%.2f%%', 100 * (1 - ($row['to_go'] / $row['size'])));
                $snatchUser = isset($row['userid']) ? format_username((int) $row['userid']) : _('Unknown');
                $username = get_anonymous((int) $row['owner']) || ($row['anonymous'] ?? '0') === '1'
                    ? ($user['class'] < UC_STAFF && (int) $row['userid'] !== (int) $user['id'] ? '' : $snatchUser . ' - ') . '<i>' . _('Kezer Soze') . '</i>'
                    : $snatchUser;

                $body .= "
        <tr>
            <td class='has-text-left'>{$username}</td>
            <td class='has-text-right'>" . mksize($row['uploaded']) . "</td>
            <td class='has-text-right'>" . htmlsafechars($upspeed) . '/s</td>' .
                    ($ratioFree ? '' : "
            <td class='has-text-right'>" . mksize($row['downloaded']) . "</td>
            <td class='has-text-right'>" . htmlsafechars($downspeed) . '/s</td>') . "
            <td class='has-text-right'>" . htmlsafechars($ratio) . "</td>
            <td class='has-text-right'>" . htmlsafechars($completed) . "</td>
            <td class='has-text-right'>" . mkprettytime((int) $row['seedtime']) . "</td>
            <td class='has-text-right'>" . mkprettytime((int) $row['leechtime']) . "</td>
            <td class='has-text-centered'>" . get_date((int) $row['last_action'], '', 0, 1) . "</td>
            <td class='has-text-centered'>" . get_date((int) $row['complete_date'], '', 0, 1) . "</td>
            <td class='has-text-centered'>" . (int) $row['timesann'] . "</td>
        </tr>";
            }

            $HTMLOUT .= main_table($body, $header);

            if ($count > $perpage) {
                $HTMLOUT .= $pager['pagerbottom'];
            }

            $title = _('Snatches');
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
