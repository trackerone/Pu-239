<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:30:40Z via handler-convert (offset=270 batch=5)

namespace PU239\Http\Handlers\PublicSite;

final class GiftHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:30:40Z via handler-convert (offset=270 batch=5)
        try {
            http_response_code(503);
            echo 'Legacy gift workflow remains disabled pending SQL/data restoration.';
            // TODO(2025): rehydrate public/gift.php:1-10 once the legacy SQL implementation is restored.
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
