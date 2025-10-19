<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T18:10:31Z via handler-convert offset=295 batch=5
// Generated: STUB_UPGRADED

namespace PU239\Http\Handlers\PublicSite;

final class TmoviesHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // STUB_UPGRADED: safe buffered execution
        // TODO(2025): extract legacy block from public/tmovies.php:1-400 (Fluent pagination + cached cast lookups)
        $target = __DIR__ . '/../../../../public/tmovies.php';
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
