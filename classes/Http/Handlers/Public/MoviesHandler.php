<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=75-5

namespace PU239\Http\Handlers\Public;

final class MoviesHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=75-5
        // TODO(2025): extract legacy block from public/movies.php:1-360 (multi-source cache hydration and helper functions)
        $target = __DIR__ . '/../../../../public/movies.php';
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

        echo $out;
    }
}
