<?php
declare(strict_types=1);

// Generated: STUB_UPGRADED

namespace PU239\Http\Handlers\Public;

final class UsercpHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T19:16:37Z via handler_convert (batch=220-224)
        // TODO(2025): extract legacy block from public/usercp.php:1-20 – routed entrypoint replaced with RuntimeException placeholder.
        // STUB_UPGRADED: safe buffered execution
        $target = __DIR__ . '/../../../../public/usercp.php';
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
