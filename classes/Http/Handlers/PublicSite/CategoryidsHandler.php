<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18 via handler-convert (offset=195 batch=5)

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class CategoryidsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18 via handler-convert (offset=195 batch=5)
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();
            $parents = genrelist(true);
            $baseurl = (string) $config->get('paths.baseurl');

            $counts = $db->fetchAll('SELECT category, COUNT(id) AS count FROM torrents GROUP BY category');

            $categoryCounts = [];
            foreach ($counts as $row) {
                $categoryId = (int) ($row['category'] ?? 0);
                $categoryCounts[$categoryId] = (int) ($row['count'] ?? 0);
            }

            $heading = "        <tr>
            <th class='has-text-centered w-25'>" . _('Cat ID') . "</th>
            <th class='has-text-centered'>" . _('Cat Name') . "</th>
            <th class='has-text-centered w-25'>" . _('Torrents Uploaded') . '</th>
        </tr>';
            $body = '';

            $child = [
                'id' => '',
                'name' => '',
            ];
            foreach ($parents as $parent) {
                if ((int) ($user['hidden'] ?? 0) === 0 && (int) ($parent['hidden'] ?? 0) === 1) {
                    continue;
                }
                foreach ($parent['children'] as $child) {
                    if ((int) ($user['hidden'] ?? 0) === 0 && (int) ($child['hidden'] ?? 0) === 1) {
                        continue;
                    }
                    $childId = (int) ($child['id'] ?? 0);
                    $count = $categoryCounts[$childId] ?? 0;
                    $parentName = (string) ($parent['name'] ?? '');
                    $childName = (string) ($child['name'] ?? '');
                    $body .= "        <tr>
            <td class='has-text-centered'>{$childId}</td>
            <td><a href='{$baseurl}/browse.php?cats[]={$childId}'>" . htmlsafechars("{$parentName}::{$childName}") . "</a></td>
            <td class='has-text-centered'>{$count}</td>
        </tr>";
                }
            }

            $HTMLOUT = "    <h1 class='has-text-centered'>" . _("Category ID's") . '</h1>';
            $HTMLOUT .= main_table($body, $heading, 'w-50 has-text-centered');
            $title = _("Category ID's");
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
