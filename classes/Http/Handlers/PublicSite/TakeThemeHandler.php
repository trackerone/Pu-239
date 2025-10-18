<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18 via handler-convert (offset=195 batch=5)

namespace PU239\Http\Handlers\PublicSite;

use PU239\Support\Audit;
use Pu239\Config\ConfigRepository;
use Pu239\User;

final class TakeThemeHandler
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
            $user = check_user_status();

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $sid = isset($_GET['id']) ? (int) $_GET['id'] : 1;
                if ($sid > 0 && $sid !== (int) $user['stylesheet']) {
                    $set = [
                        'stylesheet' => $sid,
                    ];
                    /** @var User $users */
                    $users = $container->get(User::class);
                    $users->update($set, (int) $user['id']);
                    Audit::log(
                        $user['id'] ?? null,
                        'config.update',
                        [
                            'keys' => ['stylesheet'],
                            'target' => $user['id'] ?? null,
                        ]
                    );
                }
            }

            $baseUrl = (string) $config->get('paths.baseurl');
            $returnto = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $baseUrl;
            header("Location: {$returnto}");
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}

// >>>>>> PU239:http-handler-6
