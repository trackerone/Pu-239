<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-03-19 via tools/handler-convert batch=offset40

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;

final class ShitListHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-03-19
        try {
            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (strpos(__FILE__, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            throw new \RuntimeException('Stubbed: missing SQL; see tools/rehydrate_v3_manifest.csv');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo $e instanceof \RuntimeException ? $e->getMessage() : 'Internal error';
        }
    }
}
