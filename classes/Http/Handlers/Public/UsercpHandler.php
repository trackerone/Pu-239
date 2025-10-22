<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-22T03:59:59Z via handler-convert offset=350 batch=5

namespace PU239\Http\Handlers\Public;

use function dirname;
use function error_log;
use function http_response_code;
use function is_file;
use function ob_get_clean;
use function ob_start;
use function sprintf;

final class UsercpHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-22T03:59:59Z via handler-convert offset=350 batch=5
        try {
            $target = dirname(__DIR__, 4) . '/public/usercp.php';
            if (!is_file($target)) {
                error_log(sprintf('STUB MISSING: %s requires %s', __FILE__, $target));
                http_response_code(500);
                echo 'Service temporarily unavailable';

                return;
            }

            // TODO(2025): legacy entry public/usercp.php currently throws RuntimeException placeholder awaiting rehydration
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
