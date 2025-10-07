<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=80-5

namespace PU239\Http\Handlers\Public;

use Pu239\Database;

final class AjaxchatHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=80-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            /** @var Database $db */
            $db = $container->get(Database::class);
            unset($db); // Legacy script expected database bootstrap side-effects.

            if (!\defined('AJAX_CHAT_PATH')) {
                error_log('AJAX_CHAT_PATH is not defined');
                http_response_code(500);
                echo 'Internal error';

                return;
            }

            require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'custom.php';
            require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'classes.php';

            check_user_status();
            new \CustomAJAXChat();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
