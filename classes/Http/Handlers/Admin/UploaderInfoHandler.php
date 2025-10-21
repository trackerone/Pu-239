<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-20T20:10:38Z via handler-convert offset=330 batch=5

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use RuntimeException;
use PDO;

use function basename;
use function dirname;
use function htmlsafechars;
use function is_string;
use function sprintf;

final class UploaderInfoHandler
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

            $perPage = 15;
            $totalUploaders = (int) $db->fetchValue(
                'SELECT COUNT(DISTINCT owner) FROM torrents',
            );
            $pager = pager($perPage, $totalUploaders, sprintf('%s/staffpanel.php?tool=uploader_info&amp;', $baseUrl));

            $sql = <<<SQL
                SELECT COUNT(t.id) AS how_many_torrents,
                       t.owner,
                       u.class,
                       u.uploaded,
                       u.downloaded
                  FROM torrents AS t
                  LEFT JOIN users AS u ON t.owner = u.id
                 GROUP BY t.owner, u.class, u.uploaded, u.downloaded
                 ORDER BY how_many_torrents DESC
                 LIMIT :limit OFFSET :offset
            SQL;

            $rows = $db->fetchAll(
                $sql,
                [
                    ':limit' => [$pager['pdo']['limit'], \PDO::PARAM_INT],
                    ':offset' => [$pager['pdo']['offset'], \PDO::PARAM_INT],
                ],
            );

            $html = '';
            if ($totalUploaders > $perPage) {
                $html .= $pager['pagertop'];
            }

            $heading = <<<HTML
    <tr>
        <th>" . _('Rank') . "</th>
        <th>" . _('Torrents') . "</th>
        <th>" . _('Member') . "</th>
        <th>" . _('Class') . "</th>
        <th>" . _('Ratio') . "</th>
        <th>" . _('Send PM') . "</th>
    </tr>
HTML;
            $body = '';
            $rank = $pager['pdo']['offset'] + 1;
            foreach ($rows as $row) {
                $ratio = member_ratio((float) ($row['uploaded'] ?? 0), (float) ($row['downloaded'] ?? 0));
                $body .= '    <tr>'
                    . "        <td>" . $rank++ . '</td>'
                    . "        <td>" . (int) $row['how_many_torrents'] . '</td>'
                    . "        <td>" . format_username((int) $row['owner']) . '</td>'
                    . "        <td>" . get_user_class_name((int) ($row['class'] ?? 0)) . '</td>'
                    . "        <td>" . $ratio . '</td>'
                    . "        <td><a href='{$baseUrl}/messages.php?action=send_message&amp;receiver=" . (int) $row['owner'] . "' class='button is-small tooltipper' title='" . _('Send PM') . "'>" . _('Send PM') . "</a></td>"
                    . '    </tr>';
            }

            $html .= main_table($body, $heading);

            if ($totalUploaders > $perPage) {
                $html .= $pager['pagerbottom'];
            }

            $title = _('Uploader Stats');
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
}
