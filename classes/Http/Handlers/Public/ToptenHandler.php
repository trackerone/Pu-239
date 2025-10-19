<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T19:01:07Z via handler-convert offset=305 batch=5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

use function array_map;
use function count;
use function dirname;
use function error_log;
use function implode;
use function mksize;
use function sprintf;
use function str_replace;

final class ToptenHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T19:01:07Z via handler-convert offset=305 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            check_user_status();

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $baseUrl = (string) $config->get('paths.baseurl');
            $imgstartbar = '<img src="https://chart.googleapis.com/chart?cht=bvg&amp;chbh=a&amp;chs=780x300&amp;chco=4D89F9,4D89F9&amp;chf=bg,s,000000';
            $imgstartpie = '<img src="https://chart.googleapis.com/chart?cht=p3&amp;chbh=a&amp;chs=780x300&amp;chco=4D89F9&amp;chf=bg,s,000000';

            $HTMLOUT = "
        <ul class='level-center bg-06'>
            <li class='margin10'><a class='is-link tooltipper' href='{$baseUrl}/topten.php' title='Top 10 Users'>Users</a></li>
            <li class='margin10'><a class='is-link tooltipper' href='{$baseUrl}/topten.php?view=t' title='Top 10 Torrents'>Torrents</a></li>
            <li class='margin10'><a class='is-link tooltipper' href='{$baseUrl}/topten.php?view=c' title='Top 10 Countries'>Countries</a></li>
        </ul>";

            $view = $_GET['view'] ?? '';

            $buildLabels = static fn(array $rows, string $field): array => array_map(
                static fn(array $row): string => str_replace(['|', ','], ' ', (string) ($row[$field] ?? 'Unknown')),
                $rows,
            );

            $buildChart = static function (string $base, array $labels, array $values): string {
                return sprintf('%s&amp;chd=t:%s&amp;chl=%s" alt="">', $base, implode(',', $values), implode('|', $labels));
            };

            if ($view === 't') {
                $HTMLOUT .= "<div class='article'><div class='article_header'><h2>Top 10 Most Active Torrents</h2></div>";
                $rows = $db->fetchAll(
                    "SELECT t.name, t.seeders, t.leechers FROM torrents AS t
                        LEFT JOIN peers AS p ON t.id = p.torrent
                        WHERE p.seeder = 'yes'
                        GROUP BY t.id
                        ORDER BY seeders + leechers DESC, seeders DESC, added
                        LIMIT 10"
                );
                if (count($rows) === 10) {
                    $totals = array_map(
                        static fn(array $row): int => (int) ($row['seeders'] ?? 0) + (int) ($row['leechers'] ?? 0),
                        $rows,
                    );
                    $labels = $buildLabels($rows, 'name');
                    $HTMLOUT .= $buildChart($imgstartpie, $labels, $totals);
                } else {
                    $HTMLOUT .= '<h4>' . _('Insufficient Torrents') . ' (' . count($rows) . ')</h4>';
                }
                $HTMLOUT .= '</div>';

                $HTMLOUT .= "<div class='article'><div class='article_header'><h2>Top 10 Most Snatched Torrents</h2></div>";
                $rows = $db->fetchAll(
                    "SELECT t.name, t.times_completed FROM torrents AS t
                        LEFT JOIN peers AS p ON t.id = p.torrent
                        WHERE p.seeder = 'yes'
                        GROUP BY t.id
                        ORDER BY times_completed DESC
                        LIMIT 10"
                );
                if (count($rows) === 10) {
                    $values = array_map(static fn(array $row): int => (int) ($row['times_completed'] ?? 0), $rows);
                    $labels = $buildLabels($rows, 'name');
                    $HTMLOUT .= $buildChart($imgstartpie, $labels, $values);
                } else {
                    $HTMLOUT .= '<h4>' . _('Insufficient Torrents') . ' (' . count($rows) . ')</h4>';
                }
                $HTMLOUT .= '</div>';

                $title = _('Top 10');
                $breadcrumbs = [sprintf("<a href='%s'>%s</a>", $_SERVER['PHP_SELF'] ?? '', $title)];
                echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
                app_halt('Exit called');
            }

            if ($view === 'c') {
                $HTMLOUT .= "<div class='article'><div class='article_header'><h2>Top 10 Countries (users)</h2></div>";
                $rows = $db->fetchAll(
                    'SELECT c.name, c.flagpic, COUNT(u.country) AS num
                        FROM countries AS c
                        LEFT JOIN users AS u ON c.id = u.country
                        GROUP BY c.name, c.flagpic
                        ORDER BY num DESC
                        LIMIT 10'
                );
                if (count($rows) === 10) {
                    $values = array_map(static fn(array $row): int => (int) ($row['num'] ?? 0), $rows);
                    $labels = $buildLabels($rows, 'name');
                    $HTMLOUT .= $buildChart($imgstartbar, $labels, $values);
                } else {
                    $HTMLOUT .= '<h4>' . _('Insufficient Countries') . ' (' . count($rows) . ')</h4>';
                }
                $HTMLOUT .= '</div>';

                $HTMLOUT .= "<div class='article'><div class='article_header'><h2>Top 10 Countries (total uploaded)</h2></div>";
                $rows = $db->fetchAll(
                    'SELECT c.name, SUM(u.uploaded) AS ul
                        FROM users AS u
                        LEFT JOIN countries AS c ON u.country = c.id
                        WHERE u.status = 0
                        GROUP BY c.name
                        ORDER BY ul DESC
                        LIMIT 10'
                );
                if (count($rows) === 10) {
                    $values = array_map(static fn(array $row): int => (int) ($row['ul'] ?? 0), $rows);
                    $labels = $buildLabels($rows, 'name');
                    $suffix = array_map(static fn(int $value): string => '(' . mksize($value) . ')', $values);
                    $HTMLOUT .= sprintf(
                        "%s&amp;chds=0,%d&amp;chxr=1,0,%d&amp;chd=t:%s&amp;chxt=x,y,x&amp;chxl=0:|%s|1:||||||||||%s\" alt=''></div>",
                        $imgstartbar,
                        max($values),
                        max($values),
                        implode(',', $values),
                        implode('|', $labels),
                        implode('|', $suffix),
                    );
                } else {
                    $HTMLOUT .= '<h4>' . _('Insufficient Countries') . ' (' . count($rows) . ')</h4>';
                }
                $HTMLOUT .= '</div>';

                $title = _('Top 10');
                $breadcrumbs = [sprintf("<a href='%s'>%s</a>", $_SERVER['PHP_SELF'] ?? '', $title)];
                echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
                app_halt('Exit called');
            }

            // Default display / Top Users
            $rows = $db->fetchAll(
                'SELECT username, uploaded FROM users WHERE status = 0 ORDER BY uploaded DESC, registered LIMIT 10'
            );
            if (count($rows) === 10) {
                $values = array_map(static fn(array $row): int => (int) ($row['uploaded'] ?? 0), $rows);
                $labels = $buildLabels($rows, 'username');
                $suffix = array_map(static fn(int $value): string => '(' . mksize($value) . ')', $values);
                $HTMLOUT .= main_div(
                    "<div class='article padding20'><div class='article_header'><h2>Top 10 Uploaders</h2></div>"
                    . sprintf(
                        "%s&amp;chds=0,%d&amp;chxr=1,0,%d&amp;chd=t:%s&amp;chxt=x,y,x&amp;chxl=0:|%s|1:||||||||||%s\" alt=''></div>",
                        $imgstartbar,
                        max($values),
                        max($values),
                        implode(',', $values),
                        implode('|', $labels),
                        implode('|', $suffix),
                    ),
                    'top20',
                );
            } else {
                $HTMLOUT .= main_div('<h4>' . _('Insufficient Uploaders') . ' (' . count($rows) . ')</h4>', 'top20', 'padding20');
            }

            $rows = $db->fetchAll(
                'SELECT username, downloaded FROM users WHERE status = 0 ORDER BY downloaded DESC, registered LIMIT 10'
            );
            if (count($rows) === 10) {
                $values = array_map(static fn(array $row): int => (int) ($row['downloaded'] ?? 0), $rows);
                $labels = $buildLabels($rows, 'username');
                $suffix = array_map(static fn(int $value): string => '(' . mksize($value) . ')', $values);
                $HTMLOUT .= main_div(
                    "<div class='article padding20'><div class='article_header'><h2>Top 10 Downloaders</h2></div>"
                    . sprintf(
                        "%s&amp;chds=0,%d&amp;chxr=1,0,%d&amp;chd=t:%s&amp;chxt=x,y,x&amp;chxl=0:|%s|1:||||||||||%s\" alt=''></div>",
                        $imgstartbar,
                        max($values),
                        max($values),
                        implode(',', $values),
                        implode('|', $labels),
                        implode('|', $suffix),
                    ),
                    'top20',
                );
            } else {
                $HTMLOUT .= main_div('<h4>' . _('Insufficient Downloaders') . ' (' . count($rows) . ')</h4>', 'top20', 'padding20');
            }

            $rows = $db->fetchAll(
                'SELECT username, uploaded / (:now - registered) AS upspeed FROM users WHERE status = 0 ORDER BY upspeed DESC LIMIT 10',
                [
                    ':now' => [TIME_NOW, \PDO::PARAM_INT],
                ],
            );
            if (count($rows) === 10) {
                $values = array_map(static fn(array $row): float => (float) ($row['upspeed'] ?? 0.0), $rows);
                $labels = $buildLabels($rows, 'username');
                $suffix = array_map(static fn(float $value): string => '(' . mksize((int) $value) . '/s)', $values);
                $HTMLOUT .= main_div(
                    "<div class='article padding20'><div class='article_header'><h2>Top 10 Fastest Uploaders</h2></div>"
                    . sprintf(
                        "%s&amp;chds=0,%f&amp;chxr=1,0,%f&amp;chd=t:%s&amp;chxt=x,y,x&amp;chxl=0:|%s|1:||||||||||%s\" alt=''></div>",
                        $imgstartbar,
                        max($values),
                        max($values),
                        implode(',', $values),
                        implode('|', $labels),
                        implode('|', $suffix),
                    ),
                    'top20',
                );
            } else {
                $HTMLOUT .= main_div('<h4>' . _('Insufficient Uploaders') . ' (' . count($rows) . ')</h4>', 'top20', 'padding20');
            }

            $rows = $db->fetchAll(
                'SELECT username, downloaded / (:now - registered) AS downspeed FROM users WHERE status = 0 ORDER BY downspeed DESC LIMIT 10',
                [
                    ':now' => [TIME_NOW, \PDO::PARAM_INT],
                ],
            );
            if (count($rows) === 10) {
                $values = array_map(static fn(array $row): float => (float) ($row['downspeed'] ?? 0.0), $rows);
                $labels = $buildLabels($rows, 'username');
                $suffix = array_map(static fn(float $value): string => '(' . mksize((int) $value) . '/s)', $values);
                $HTMLOUT .= main_div(
                    "<div class='article padding20'><div class='article_header'><h2>Top 10 Fastest Downloaders</h2></div>"
                    . sprintf(
                        "%s&amp;chds=0,%f&amp;chxr=1,0,%f&amp;chd=t:%s&amp;chxt=x,y,x&amp;chxl=0:|%s|1:||||||||||%s\" alt=''></div>",
                        $imgstartbar,
                        max($values),
                        max($values),
                        implode(',', $values),
                        implode('|', $labels),
                        implode('|', $suffix),
                    ),
                    'top20',
                );
            } else {
                $HTMLOUT .= main_div('<h4>' . _('Insufficient Downloaders') . ' (' . count($rows) . ')</h4>', 'top20', 'padding20');
            }

            $title = _('Top 10');
            $breadcrumbs = [sprintf("<a href='%s'>%s</a>", $_SERVER['PHP_SELF'] ?? '', $title)];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
