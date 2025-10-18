<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:54:37Z via handler-convert offset=215 size=5

namespace PU239\Http\Handlers\Public;

final class BootstrapWebHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:54:37Z via handler-convert offset=215 size=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
