<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=65-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Cache;
use Pu239\Database;
use PU239\Config\ConfigRepository;

final class AutocompleteHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=65-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $s = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            check_user_status();

            // TODO(2025): csrf
            $keyword = trim((string) ($_POST['keyword'] ?? ''));
            if ($keyword === '' || mb_strlen($keyword) < 2) {
                return;
            }

            $keywordLower = strtolower($keyword);
            $keywordSafe = $s($keywordLower);
            $cacheKey = 'suggest_torrents_' . hash('sha256', $keywordLower);

            $results = $cache->get($cacheKey);
            if ($results === false || $results === null) {
                $sql = <<<SQL
                    SELECT id, name, seeders, leechers, visible
                    FROM torrents
                    WHERE LOWER(name) LIKE :name
                    ORDER BY id DESC
                    LIMIT 25
                SQL;
                $results = $db->toArray($sql, ['name' => '%' . $keywordLower . '%']);
                $cache->set($cacheKey, $results, 300);

                $hashes = $cache->get('suggest_torrents_hashes_');
                if (!is_array($hashes)) {
                    $hashes = [];
                }
                if (!in_array($cacheKey, $hashes, true)) {
                    $hashes[] = $cacheKey;
                    $cache->set('suggest_torrents_hashes_', $hashes, 300);
                }
            }

            $template = "
        <ul>
            <li class='has-text-centered'>" . _fe("No results. Try refining your search for '{0}'.", $keywordSafe) . '</li>
        </ul>';

            if ($results !== []) {
                $template = "
        <ul class='columns has-text-wight-bold'>
            <li class='column is-three-fifth'>
                <span class='size_5 is-bold'>" . _('Name') . "</span>
            </li>
            <li class='column is-one-fifth has-text-centered'>
                <span class='size_5 is-bold'>" . _('Seeders') . "</span>
            </li>
            <li class='column is-one-fifth has-text-centered'>
                <span class='size_5 is-bold'>" . _('Leechers') . '</span>
            </li>
        </ul>';

                $rowIndex = 0;
                $baseUrl = $s($config->get('paths.baseurl'));
                foreach ($results as $result) {
                    $rowIndex++;
                    $color = $result['visible'] === 'yes' ? 'is-success' : 'has-text-danger';
                    $background = $rowIndex % 2 === 0 ? 'bg-04' : 'bg-03';
                    $name = $s($result['name']);
                    $seeders = (int) $result['seeders'];
                    $leechers = (int) $result['leechers'];
                    $torrentId = (int) $result['id'];
                    $template .= "
        <ul class='columns {$background} round10'>
            <li class='column is-three-fifth'>
                <a href='{$baseUrl}/details.php?id={$torrentId}&amp;hit=1'>
                    <span class='{$color}'>{$name}</span>
                </a>
            </li>
            <li class='column is-one-fifth has-text-centered'>
                <span class='{$color}'>{$seeders}</span>
            </li>
            <li class='column is-one-fifth has-text-centered'>
                <span class='{$color}'>{$leechers}</span>
            </li>
        </ul>";
                }
            }

            // TODO(2025): review escaping strategy for $template output
            echo $template; // noescape
            echo $template;
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
