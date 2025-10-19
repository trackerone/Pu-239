<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:55:00Z via handler-convert offset=280 batch=5

namespace PU239\Http\Handlers\PublicSite;

use PU239\Support\Audit;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\User;

use function dirname;

final class RestoreclassHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:55:00Z via handler-convert offset=280 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/helpers/audit.php';

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
            /** @var User $users */
            $users = $container->get(User::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $user = check_user_status();

            $set = [
                'override_class' => 255,
            ];
            $previousOverride = $user['override_class'] ?? null;

            $users->update($set, (int) $user['id']);
            Audit::log(
                $user['id'] ?? null,
                'role.change',
                [
                    'target' => $user['id'] ?? null,
                    'from' => $previousOverride,
                    'to' => $set['override_class'],
                ],
            );

            $db->run(
                'DELETE FROM ajax_chat_online WHERE userID = :userID',
                [
                    ':userID' => $user['id'],
                ],
            );

            $cache->delete('chat_users_list_');

            header('Location: ' . $config->get('paths.baseurl'));
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
