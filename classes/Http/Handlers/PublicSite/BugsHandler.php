<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-21T04:35:34Z via handler-convert offset=345 batch=5

namespace PU239\Http\Handlers\PublicSite;

final class BugsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-21T04:35:34Z via handler-convert offset=345 batch=5
        try {
            $target = __DIR__ . '/../../../../public/bugs.php';
            if (!is_file($target)) {
                error_log(sprintf('STUB MISSING: %s requires %s', __FILE__, $target));
                http_response_code(500);
                echo 'Service temporarily unavailable';

                return;
            }

            // TODO(2025): extract legacy block from public/bugs.php:1-520 (multi-action bug tracker + messaging + audit writes)
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
