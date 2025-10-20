<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-20T04:13:49Z via handler-convert offset=320 batch=5

namespace PU239\Http\Handlers\PublicSite;

use function dirname;
use function ob_get_clean;
use function ob_start;

final class WikiHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-20T04:13:49Z via handler-convert offset=320 batch=5
        try {
            $target = dirname(__DIR__, 4) . '/public/wiki.php';
            if (!is_file($target)) {
                error_log(sprintf('STUB MISSING: %s requires %s', __FILE__, $target));
                http_response_code(500);
                echo 'Service temporarily unavailable';

                return;
            }

            // TODO(2025): extract legacy block from public/wiki.php:1-400 (complex validator/session/wiki service interactions)
            ob_start();
            try {
                require $target;
            } catch (\Throwable $legacyException) {
                error_log('Legacy stub error: ' . $legacyException->getMessage());
            }
            $out = (string) ob_get_clean();

            echo $out;
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
