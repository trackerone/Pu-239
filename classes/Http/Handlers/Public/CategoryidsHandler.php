<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=80-5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class CategoryidsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=80-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();
            $parents = genrelist(true);
            $baseUrl = (string) $config->get('paths.baseurl');

            $countRows = $db->fetchAll('SELECT category, COUNT(id) AS count FROM torrents GROUP BY category');
            $counts = [];
            foreach ($countRows as $row) {
                if (!isset($row['category'])) {
                    continue;
                }
                $categoryId = (int) $row['category'];
                $counts[$categoryId] = (int) ($row['count'] ?? 0);
            }

            $heading = "
        <tr>
            <th class='has-text-centered w-25'>" . _('Cat ID') . "</th>
            <th class='has-text-centered'>" . _('Cat Name') . "</th>
            <th class='has-text-centered w-25'>" . _('Torrents Uploaded') . '</th>
        </tr>';
            $body = '';

            foreach ($parents as $parent) {
                if (!$user['hidden'] && (int) ($parent['hidden'] ?? 0) === 1) {
                    continue;
                }

                $children = $parent['children'] ?? [];
                if (!\is_array($children)) {
                    continue;
                }

                foreach ($children as $child) {
                    if (!$user['hidden'] && (int) ($child['hidden'] ?? 0) === 1) {
                        continue;
                    }

                    $childId = (int) ($child['id'] ?? 0);
                    $count = $counts[$childId] ?? 0;
                    $body .= "
        <tr>
            <td class='has-text-centered'>{$childId}</td>
            <td><a href='{$baseUrl}/browse.php?cats[]={$childId}'>{$parent['name']}::{$child['name']}</a></td>
            <td class='has-text-centered'>{$count}</td>
        </tr>";
                }
            }

            $HTMLOUT = "
    <h1 class='has-text-centered'>" . _("Category ID's") . '</h1>';
            $HTMLOUT .= main_table($body, $heading, 'w-50 has-text-centered');
            $title = _("Category ID's");
            $breadcrumbs = [
                sprintf("<a href='%s'>%s</a>", $_SERVER['PHP_SELF'] ?? '', $title),
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
