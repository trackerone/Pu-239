<?php
declare(strict_types=1);

// AUTO-CONVERT

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Database;

final class AjaxchatHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T19:16:37Z via handler_convert (batch=220-224)

        try {
            global $container;

            /** @var Database $db */
            $db = $container->get(Database::class);

            require_once __DIR__ . '/../../../../include/bittorrent.php';
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
