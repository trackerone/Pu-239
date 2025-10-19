<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:43:44Z via handler-convert offset=275 batch=5

namespace PU239\Http\Handlers\PublicSite;

use function error_log;
use function http_response_code;
use function is_file;
use function ob_get_clean;
use function ob_start;
use function sprintf;

final class ReputationHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:43:44Z via handler-convert offset=275 batch=5
        try {
            $target = __DIR__ . '/../../../../public/reputation.php';
            if (!is_file($target)) {
                error_log(sprintf('STUB MISSING: %s requires %s', __FILE__, $target));
                http_response_code(500);
                echo 'Service temporarily unavailable';
                return;
            }

            // TODO(2025): extract legacy block from public/reputation.php:1-20 (handler replaced by RuntimeException stub)
            $out = (static function (string $file): string {
                ob_start();
                try {
                    require $file;
                } catch (\Throwable $e) {
                    error_log('Legacy stub error: ' . $e->getMessage());
                }

                return (string) ob_get_clean();
            })($target);

            echo $out;
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
