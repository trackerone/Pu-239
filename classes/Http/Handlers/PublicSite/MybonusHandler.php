<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-20T04:28:54Z via handler-convert offset=325 batch=5
// Generated: STUB_UPGRADED

namespace PU239\Http\Handlers\PublicSite;

final class MybonusHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // STUB_UPGRADED: safe buffered execution
        // TODO(2025): extract legacy block from public/mybonus.php:1-841
        //   (karma store controller with ExtendedPDO placeholders + transactions)
        $target = __DIR__ . '/../../../../public/mybonus.php';
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
