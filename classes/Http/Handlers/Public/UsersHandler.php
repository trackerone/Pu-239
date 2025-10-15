<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-15 via handler-convert offset=135 batch=5

namespace PU239\Http\Handlers\Public;

final class UsersHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-15 via handler-convert offset=135 batch=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            // TODO(2025): restore user list view from public/users.php:10
            throw new \RuntimeException('Stubbed: missing SQL; see tools/rehydrate_v3_manifest.csv');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
