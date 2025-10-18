<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T20:00:53Z via handler_convert (offset=235 batch=5)

namespace PU239\Http\Handlers\PublicSite;

final class FriendsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T20:00:53Z via handler_convert (offset=235 batch=5)
        try {
            http_response_code(503);
            echo 'Legacy friends list is unavailable pending SQL restoration.';
            // TODO(2025): restore friends management workflow from public/friends.php:1-10 when data layer returns.
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
