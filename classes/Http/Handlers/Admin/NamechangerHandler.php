<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T20:19:43Z via handler-convert offset=240 batch=5

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;

final class NamechangerHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // TODO(2025): rehydrate admin/namechanger.php logic once legacy SQL is restored.
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            AuthZ::requireRole('admin');

            throw new \RuntimeException('Stubbed: missing SQL; see tools/rehydrate_v3_manifest.csv');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
