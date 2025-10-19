<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19 via handler-convert offset=260 batch=5

namespace PU239\Http\Handlers\PublicSite;

final class FlashHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19 via handler-convert offset=260 batch=5
        try {
            // TODO(2025): extract legacy block from public/flash.php:1-200 – manual mysqli/sql_query rewrite and ZipArchive piping required.
            $target = __DIR__ . '/../../../../public/flash.php';
            if (!is_file($target)) {
                error_log(sprintf('STUB MISSING: %s requires %s', __FILE__, $target));
                http_response_code(500);
                echo 'Service temporarily unavailable';

                return;
            }

            $out = (static function (string $file): string {
                ob_start();
                try {
                    require $file;
                } catch (\Throwable $throwable) {
                    error_log('Legacy stub error: ' . $throwable->getMessage());
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
