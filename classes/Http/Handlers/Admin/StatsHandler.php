<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-20T20:10:38Z via handler-convert offset=330 batch=5

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Roles;
use RuntimeException;

use function basename;
use function dirname;
use function htmlsafechars;
use function is_string;
use function number_format;
use function sprintf;

final class StatsHandler
{
    /** @param array<string, mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-20T20:10:38Z via handler-convert offset=330 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            AuthZ::requireRole('admin');

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $class = get_access(basename(is_string($requestUri) ? $requestUri : ''));
            class_check($class);

            $baseUrl = (string) $config->get('paths.baseurl');

            $totalTorrents = (int) $db->fetchValue('SELECT COUNT(id) FROM torrents');
            $totalPeers = (int) $db->fetchValue('SELECT COUNT(id) FROM peers');

            $uporder = $_GET['uporder'] ?? '';
            $catorder = $_GET['catorder'] ?? '';

            $uploaderOrder = $this->resolveUploaderOrder((string) $uporder);
            $uploaderCount = (int) $db->fetchValue(
                'SELECT COUNT(*) FROM users WHERE (roles_mask & :mask) != 0',
                [':mask' => Roles::UPLOADER],
            );

            $perPage = 25;
            $pager = pager($perPage, $uploaderCount, sprintf('%s/staffpanel.php?tool=stats&amp;', $baseUrl));
            $uploaderParams = [
                ':mask' => Roles::UPLOADER,
            ];
            $limitClause = '';
            if ($uploaderCount > $perPage) {
                $limitClause = ' LIMIT :limit OFFSET :offset';
                $uploaderParams[':limit'] = [$pager['pdo']['limit'], \PDO::PARAM_INT];
                $uploaderParams[':offset'] = [$pager['pdo']['offset'], \PDO::PARAM_INT];
            }

            $uploaderSql = <<<SQL
                SELECT u.id,
                       u.username AS name,
                       MAX(t.added) AS last,
                       COUNT(DISTINCT t.id) AS n_t,
                       COUNT(p.id) AS n_p
                  FROM users AS u
                  LEFT JOIN torrents AS t ON u.id = t.owner
                  LEFT JOIN peers AS p ON t.id = p.torrent
                 WHERE (u.roles_mask & :mask) != 0
                 GROUP BY u.id, u.username
                 ORDER BY {$uploaderOrder}{$limitClause}
            SQL;
            $uploaders = $db->fetchAll($uploaderSql, $uploaderParams);

            $html = '';
            if ($uploaderCount === 0) {
                stdmsg(_('Error'), _('No uploaders.'));
            } else {
                if ($uploaderCount > $perPage) {
                    $html .= $pager['pagertop'];
                }

                $heading = <<<HTML
    <tr>
        <th><a href='{$baseUrl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=uploader&amp;catorder={$catorder}' class='colheadlink'>" . _('Uploader') . "</a></th>
        <th><a href='{$baseUrl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=lastul&amp;catorder={$catorder}' class='colheadlink'>" . _('Last upload') . "</a></th>
        <th><a href='{$baseUrl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=torrents&amp;catorder={$catorder}' class='colheadlink'>" . _('Torrents') . "</a></th>
        <th>" . _('Perc.') . "</th>
        <th><a href='{$baseUrl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=peers&amp;catorder={$catorder}' class='colheadlink'>" . _('Peers') . "</a></th>
        <th>" . _('Perc.') . "</th>
    </tr>
HTML;
                $body = '';
                foreach ($uploaders as $row) {
                    $last = $row['last']
                        ? get_date((int) $row['last'], '') . ' (' . get_date((int) $row['last'], '', 0, 1) . ')'
                        : '---';
                    $body .= '    <tr>'
                        . "        <td>" . format_username((int) $row['id']) . '</td>'
                        . "        <td>" . $last . '</td>'
                        . "        <td>" . (int) $row['n_t'] . '</td>'
                        . "        <td>" . ($totalTorrents > 0 ? number_format((float) ($row['n_t'] / $totalTorrents) * 100, 1) . '%' : '---') . '</td>'
                        . "        <td>" . (int) $row['n_p'] . '</td>'
                        . "        <td>" . ($totalPeers > 0 ? number_format((float) ($row['n_p'] / $totalPeers) * 100, 1) . '%' : '---') . '</td>'
                        . '    </tr>';
                }

                $html .= main_table($body, $heading);
                if ($uploaderCount > $perPage) {
                    $html .= $pager['pagerbottom'];
                }
            }

            if ($totalTorrents === 0) {
                stdmsg(_('Error'), _('No categories defined!'));
            } else {
                $categoryOrder = $this->resolveCategoryOrder((string) $catorder);
                $categories = $db->fetchAll(
                    "SELECT c.name,
                            MAX(t.added) AS last,
                            COUNT(DISTINCT t.id) AS n_t,
                            COUNT(p.id) AS n_p
                       FROM categories AS c
                       LEFT JOIN torrents AS t ON t.category = c.id
                       LEFT JOIN peers AS p ON t.id = p.torrent
                      GROUP BY c.id, c.name
                      ORDER BY {$categoryOrder}",
                );

                $heading = <<<HTML
    <tr>
        <th><a href='{$baseUrl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder={$uporder}&amp;catorder=category' class='colheadlink'>" . _('Category') . "</a></th>
        <th><a href='{$baseUrl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder={$uporder}&amp;catorder=lastul' class='colheadlink'>" . _('Last upload') . "</a></th>
        <th><a href='{$baseUrl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder={$uporder}&amp;catorder=torrents' class='colheadlink'>" . _('Torrents') . "</a></th>
        <th>" . _('Perc.') . "</th>
        <th><a href='{$baseUrl}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder={$uporder}&amp;catorder=peers' class='colheadlink'>" . _('Peers') . "</a></th>
        <th>" . _('Perc.') . "</th>
    </tr>
HTML;
                $body = '';
                foreach ($categories as $row) {
                    $last = $row['last']
                        ? get_date((int) $row['last'], '') . ' (' . get_date((int) $row['last'], '', 0, 1) . ')'
                        : '---';
                    $body .= '    <tr>'
                        . "        <td>" . htmlsafechars((string) $row['name']) . '</td>'
                        . "        <td>" . $last . '</td>'
                        . "        <td>" . (int) $row['n_t'] . '</td>'
                        . "        <td>" . number_format((float) ($row['n_t'] / $totalTorrents) * 100, 1) . "%</td>"
                        . "        <td>" . (int) $row['n_p'] . '</td>'
                        . "        <td>" . ($totalPeers > 0 ? number_format((float) ($row['n_p'] / $totalPeers) * 100, 1) . '%' : '---') . '</td>'
                        . '    </tr>';
                }

                $html .= main_table($body, $heading, null, 'top20');
            }

            $title = _('Stats');
            $breadcrumbs = [
                "<a href='{$baseUrl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    private function resolveUploaderOrder(string $order): string
    {
        return match ($order) {
            'lastul' => 'last DESC, name',
            'torrents' => 'n_t DESC, name',
            'peers' => 'n_p DESC, name',
            default => 'name',
        };
    }

    private function resolveCategoryOrder(string $order): string
    {
        return match ($order) {
            'lastul' => 'last DESC, c.name',
            'torrents' => 'n_t DESC, c.name',
            'peers' => 'n_p DESC, c.name',
            default => 'c.name',
        };
    }
}
