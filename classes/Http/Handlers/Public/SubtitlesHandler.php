<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:54:37Z via handler-convert offset=215 size=5

namespace PU239\Http\Handlers\Public;

final class SubtitlesHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // TODO(2025): extract legacy workflow from public/subtitles.php:1-520 (multi-branch upload/edit/delete/search flow with FluentPDO replacements)
        $target = __DIR__ . '/../../../../public/subtitles.php';
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
