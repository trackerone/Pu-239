<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:09:15Z via handler-convert offset=200 size=5 (deferred)

namespace PU239\Http\Handlers\Public;

final class TakeuploadHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:09:15Z via handler-convert offset=200 size=5 (deferred)
        // TODO(2025): extract legacy block from public/takeupload.php:1-400 (upload guard, bonus payouts, FluentPDO rewrites)
        // TODO(2025): re-reviewed at offset=200; blocked pending PDO transaction + validation parity
        $target = __DIR__ . '/../../../../public/takeupload.php';
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
            } catch (\Throwable $e) {
                error_log('Legacy stub error: ' . $e->getMessage());
            }
            return (string) ob_get_clean();
        })($target);

        // Optional: allow middleware or further processing here
        echo $out;
    }
}
