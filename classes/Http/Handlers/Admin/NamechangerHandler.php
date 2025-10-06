<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05 via handler-convert (batch=7)
// Generated: STUB_UPGRADED

namespace PU239\Http\Handlers\Admin;

final class NamechangerHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // STUB_UPGRADED: safe buffered execution
        // TODO(2025): extract legacy block from admin/namechanger.php:1-40 (runtime stub -> RuntimeException)
        $target = __DIR__ . '/../../../../admin/namechanger.php';
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
