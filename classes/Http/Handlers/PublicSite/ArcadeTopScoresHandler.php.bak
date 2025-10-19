<?php
declare(strict_types=1);

// Generated: STUB_UPGRADED

namespace PU239\Http\Handlers\PublicSite;

final class ArcadeTopScoresHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // STUB_UPGRADED: safe buffered execution
        $target = __DIR__ . '/../../../../public/arcade_top_scores.php';
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
