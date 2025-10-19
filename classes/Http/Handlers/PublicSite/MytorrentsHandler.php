<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T21:32:58Z via handler-convert offset=250 batch=5

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;
use Pu239\Database;

use function dirname;
use function htmlsafechars;

final class MytorrentsHandler
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

            $user = check_user_status();
            $minVotes = (int) $config->get('site.minvotes');
            $baseurl = (string) $config->get('paths.baseurl');

            $HTMLOUT = '';

            $countQuery = $fluent->from('torrents AS t')
                ->select(null)
                ->select('COUNT(id) AS count');

            $selectQuery = $fluent->from('torrents AS t')
                ->select('IF(t.num_ratings < ' . $minVotes . ', NULL, ROUND(t.rating_sum / t.num_ratings, 1)) AS rating')
                ->select('IF(s.to_go IS NOT NULL, (t.size - s.to_go) / t.size, -1) AS to_go')
                ->select('u.class')
                ->select('u.username')
                ->where('s.userid = ?', $user['id'])
                ->leftJoin('snatched AS s ON t.id = s.torrentid')
                ->leftJoin('users AS u ON t.owner = u.id');

            $pagerlink = '';
            if (isset($_GET['sort'], $_GET['type'])) {
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
                $columnIndex = (int) $_GET['sort'];
                $column = $validSort[$columnIndex] ?? $validSort[0];
                $type = htmlsafechars($_GET['type']);
                switch ($type) {
                    case 'asc':
                        $ascdesc = '';
                        $linkascdesc = 'asc';
                        break;
                    default:
                        $ascdesc = 'DESC';
                        $linkascdesc = 'desc';
                        break;
                }
                $selectQuery = $selectQuery->orderBy("t.{$column} {$ascdesc}");
                $pagerlink = 'sort=' . $columnIndex . '&amp;type=' . $linkascdesc . '&amp;';
            } else {
                $selectQuery = $selectQuery->orderBy('t.staff_picks DESC')
                    ->orderBy('t.sticky')
                    ->orderBy('t.added DESC');
            }

            $count = $countQuery
                ->where('owner = ?', $user['id'])
                ->where('banned != "yes"')
                ->fetch('count');

            $select = $selectQuery
                ->where('owner = ?', $user['id'])
                ->where('banned != "yes"');

            if (!$count) {
                $HTMLOUT .= "
        <h1 class='has-text-centered'>" . _('No torrents') . '</h1>' . main_div("
        <div class='has-text-centered'>" . _("You haven't uploaded any torrents yet.") . '</div>', null, 'padding20');
            } else {
                $pager = pager(20, $count, "{$baseurl}/mytorrents.php?{$pagerlink}");
                $rows = $select
                    ->limit($pager['pdo']['limit'])
                    ->offset($pager['pdo']['offset'])
                    ->fetchAll();
                $HTMLOUT .= $pager['pagertop'];
                $HTMLOUT .= torrenttable($rows, $user, 'mytorrents');
                $HTMLOUT .= $pager['pagerbottom'];
            }

            $title = _('My Torrents');
            $breadcrumbs = [
                "<a href='{$baseurl}/browse.php'>" . _('Browse Torrents') . '</a>',
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
