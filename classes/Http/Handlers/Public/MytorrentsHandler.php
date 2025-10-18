<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:40:29Z via handler-convert offset=210 size=5

namespace PU239\Http\Handlers\Public;

use PU239\Config\ConfigRepository;
use Pu239\Database;
use RuntimeException;
use PDO;

final class MytorrentsHandler
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

            $user = check_user_status();
            $minVotes = (int) $config->get('site.minvotes');
            $baseUrl = (string) $config->get('paths.baseurl');

            $validSort = [
                'id',
                'name',
                'numfiles',
                'comments',
                'added',
                'size',
                'times_completed',
                'seeders',
                'leechers',
                'owner',
            ];

            $columnIndex = isset($_GET['sort']) && is_scalar($_GET['sort']) ? (int) $_GET['sort'] : 0;
            $column = $validSort[$columnIndex] ?? $validSort[0];
            $typeParam = isset($_GET['type']) && is_scalar($_GET['type']) ? htmlsafechars((string) $_GET['type']) : '';

            if (isset($_GET['sort'], $_GET['type'])) {
                if ($typeParam === 'asc') {
                    $direction = 'ASC';
                    $linkAscDesc = 'asc';
                } else {
                    $direction = 'DESC';
                    $linkAscDesc = 'desc';
                }

                $orderClause = "ORDER BY t.{$column} {$direction}";
                $pagerlink = 'sort=' . $columnIndex . '&amp;type=' . $linkAscDesc . '&amp;';
            } else {
                $orderClause = 'ORDER BY t.staff_picks DESC, t.sticky, t.added DESC';
                $pagerlink = '';
            }

            $count = (int) ($db->fetchValue(
                'SELECT COUNT(t.id)
                    FROM torrents AS t
                    WHERE t.owner = :owner AND t.banned <> :banned',
                [
                    'owner' => [$user['id'], PDO::PARAM_INT],
                    'banned' => 'yes',
                ],
            ) ?? 0);

            $HTMLOUT = '';
            if ($count === 0) {
                $HTMLOUT .= "
        <h1 class='has-text-centered'>" . _('No torrents') . '</h1>' . main_div("\n        <div class='has-text-centered'>" . _("You haven't uploaded any torrents yet.") . '</div>', null, 'padding20');
            } else {
                $pager = pager(20, $count, "{$baseUrl}/mytorrents.php?{$pagerlink}");
                $sql = <<<SQL
                    SELECT t.*,
                        CASE WHEN t.num_ratings < :minVotes THEN NULL ELSE ROUND(t.rating_sum / t.num_ratings, 1) END AS rating,
                        CASE WHEN s.to_go IS NOT NULL THEN (t.size - s.to_go) / t.size ELSE -1 END AS to_go,
                        u.class,
                        u.username
                    FROM torrents AS t
                    LEFT JOIN snatched AS s ON t.id = s.torrentid AND s.userid = :snatchedUserId
                    LEFT JOIN users AS u ON t.owner = u.id
                    WHERE t.owner = :owner AND t.banned <> :banned
                    {$orderClause}
                    LIMIT :limit OFFSET :offset
                SQL;

                $rows = $db->toArray(
                    $sql,
                    [
                        'minVotes' => $minVotes,
                        'snatchedUserId' => [$user['id'], PDO::PARAM_INT],
                        'owner' => [$user['id'], PDO::PARAM_INT],
                        'banned' => 'yes',
                        'limit' => [$pager['pdo']['limit'], PDO::PARAM_INT],
                        'offset' => [$pager['pdo']['offset'], PDO::PARAM_INT],
                    ],
                );

                $HTMLOUT .= $pager['pagertop'];
                $HTMLOUT .= torrenttable($rows, $user, 'mytorrents');
                $HTMLOUT .= $pager['pagerbottom'];
            }

            $title = _('My Torrents');
            $breadcrumbs = [
                "<a href='{$baseUrl}/browse.php'>" . _('Browse Torrents') . '</a>',
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
