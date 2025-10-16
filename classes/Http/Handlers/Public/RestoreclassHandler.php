<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16T04:02:33Z via handler-convert offset=155 size=5

namespace PU239\Http\Handlers\Public;

use PU239\Support\Audit;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\User;
use RuntimeException;

final class RestoreclassHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16T04:02:33Z via handler-convert offset=155 size=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $baseUrl = (string) $config->get('paths.baseurl');

            $user = check_user_status();
            $set = [
                'override_class' => 255,
            ];
            $previousOverride = $user['override_class'] ?? null;

            /** @var User $users */
            $users = $container->get(User::class);
            $users->update($set, $user['id']);

            Audit::log(
                $user['id'] ?? null,
                'role.change',
                [
                    'target' => $user['id'] ?? null,
                    'from' => $previousOverride,
                    'to' => $set['override_class'],
                ],
            );

            /** @var Database $db */
            $db = $container->get(Database::class);
            $db->perform(
                'DELETE FROM ajax_chat_online WHERE userID = :userID',
                [
                    'userID' => $user['id'],
                ],
            );

            /** @var Cache $cache */
            $cache = $container->get(Cache::class);
            $cache->delete('chat_users_list_');

            header('Location: ' . $baseUrl);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
