<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:30:40Z via handler-convert (offset=270 batch=5)

namespace PU239\Http\Handlers\PublicSite;

final class LotteryHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:30:40Z via handler-convert (offset=270 batch=5)
        try {
            $target = __DIR__ . '/../../../../public/lottery.php';
            if (!is_file($target)) {
                error_log(sprintf('STUB MISSING: %s requires %s', __FILE__, $target));
                http_response_code(500);
                echo 'Service temporarily unavailable';

                return;
            }

            $out = (static function (string $file): string {
                ob_start();
                try {
                    // TODO(2025): extract lottery controller from public/lottery.php:1-200 (nested includes + mysqli usage).
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
