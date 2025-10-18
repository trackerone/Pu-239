<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:47:17Z via handler-convert offset=185 size=5

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;
use RuntimeException;

final class TopmoodsHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:47:17Z via handler-convert offset=185 size=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $user = check_user_status();
            $baseUrl = (string) $config->get('paths.baseurl');
            $imagesBaseUrl = (string) $config->get('paths.images_baseurl');

            $htmlOut = "";
            $intro = "
        <h1 class=\"has-text-centered\">Top Moods</h1>
        <div class=\"has-text-centered bottom20\">You may select your mood by clicking on the smiley in the left side menu or clicking <a href=\"javascript:;\" onclick=\"PopUp('usermood.php','Mood',530,500,1,1);\"><span class=\"has-text-success\">here</span></a>.</div>
         <table class=\"table table-bordered table-striped\">
         <tr><td class=\"colhead\">Count</td>
         <td class=\"colhead\">Mood</td>
         <td class=\"colhead\">Icon</td>
         </tr>";
            $cacheKey = 'topmoods';
            $topMoods = $cache->get($cacheKey);
            if ($topMoods === false || $topMoods === null) {
                $rows = $db->fetchAll(
                    'SELECT moods.*, users.mood, COUNT(users.mood) AS moodcount '
                    . 'FROM users LEFT JOIN moods ON (users.mood = moods.id) '
                    . 'GROUP BY users.mood '
                    . 'ORDER BY moodcount DESC, moods.id'
                );
                $buffer = '';
                foreach ($rows as $row) {
                    $bonusLink = ((int) ($row['bonus'] ?? 0) === 1) ? "<a href='{$baseUrl}/mybonus.php'>(bonus)</a>" : '';
                    $buffer .= sprintf("<tr><td>%d</td><td>%s %s</td><td><img src='%ssmilies/%s' alt=''></td></tr>",
                        (int) ($row['moodcount'] ?? 0),
                        htmlsafechars($row['name'] ?? ''),
                        $bonusLink,
                        $imagesBaseUrl,
                        htmlsafechars($row['image'] ?? '')
                    );
                }
                $topMoods = $buffer;
                $cache->set($cacheKey, $topMoods, 0);
            }

            $htmlOut .= $intro . $topMoods . '</table>';
            $title = _('Top Moods');
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
}
