<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T20:00:53Z via handler_convert (offset=235 batch=5)

namespace PU239\Http\Handlers\PublicSite;

final class MessagesHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T20:00:53Z via handler_convert (offset=235 batch=5)
        try {
            http_response_code(503);
            echo 'Legacy messaging UI disabled until legacy SQL is rehydrated.';
            // TODO(2025): rebuild messaging front-end from public/messages.php:1-10 after restoring legacy schema.
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
