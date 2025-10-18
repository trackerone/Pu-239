<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T20:19:43Z via handler-convert offset=240 batch=5

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;

final class WarnHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // TODO(2025): rebuild staff warning workflow when legacy SQL is ported.
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            if (strpos(__FILE__, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            throw new \RuntimeException('Stubbed: missing SQL; see tools/rehydrate_v3_manifest.csv');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
