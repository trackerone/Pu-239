<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18 via handler-convert (offset=195 batch=5)

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class ClearAnnouncementHandler
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

            $db->run(
                'UPDATE users SET curr_ann_id = :curr_ann_id, curr_ann_last_check = :curr_ann_last_check WHERE id = :id AND curr_ann_id != 0',
                [
                    ':curr_ann_id' => 0,
                    ':curr_ann_last_check' => 0,
                    ':id' => (int) $user['id'],
                ]
            );
            audit_log($user['id'] ?? null, 'announcement.clear', []);

            /** @var Cache $cache */
            $cache = $container->get(Cache::class);
            $cache->update_row(
                'user_' . $user['id'],
                [
                    'curr_ann_id' => 0,
                    'curr_ann_last_check' => 0,
                ],
                (int) $config->get('expires.user_cache')
            );

            header('Location: ' . $config->get('paths.baseurl'));
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
