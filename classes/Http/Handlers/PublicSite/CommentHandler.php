<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T20:00:53Z via handler_convert (offset=235 batch=5)

namespace PU239\Http\Handlers\PublicSite;

final class CommentHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T20:00:53Z via handler_convert (offset=235 batch=5)
        try {
            http_response_code(503);
            echo 'Legacy comment endpoint disabled pending data restore.';
            // TODO(2025): rebuild comment posting workflow from public/comment.php:1-10 once legacy SQL is recovered.
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
