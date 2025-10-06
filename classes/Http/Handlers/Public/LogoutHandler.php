<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=50-5

namespace PU239\Http\Handlers\Public;

use Delight\Auth\Auth;
use Pu239\Config\ConfigRepository;
use Pu239\User;

final class LogoutHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=50-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $baseUrl = (string) $config->get('paths.baseurl');

            /** @var Auth $auth */
            $auth = $container->get(Auth::class);
            if ($auth->isLoggedIn()) {
                $userId = $auth->getUserId();
                if (!empty($userId)) {
                    /** @var User $user */
                    $user = $container->get(User::class);
                    $user->logout((int) $userId, true);
                }
            }

            header(sprintf('Location: %s/login.php', $baseUrl));
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
