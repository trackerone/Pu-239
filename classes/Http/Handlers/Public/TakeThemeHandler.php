<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-10 via handler-convert (batch=120-5)

namespace PU239\Http\Handlers\Public;

use PU239\Support\Audit;
use Pu239\Config\ConfigRepository;
use Pu239\User;

final class TakeThemeHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-10 via handler-convert (batch=120-5)
        try {
            global $container;

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';
            $user = check_user_status();

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $sid = isset($_GET['id']) ? (int) $_GET['id'] : 1;
                if ($sid > 0 && $sid !== (int) ($user['stylesheet'] ?? 0)) {
                    $set = [
                        'stylesheet' => $sid,
                    ];
                    $users = $container->get(User::class);
                    $users->update($set, (int) ($user['id'] ?? 0));
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
            $returnTo = $_SERVER['HTTP_REFERER'] ?? $baseUrl;
            header("Location: $returnTo");
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
