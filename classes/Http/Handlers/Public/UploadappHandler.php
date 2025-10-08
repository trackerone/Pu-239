<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=90-5

namespace PU239\Http\Handlers\Public;

final class UploadappHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        $target = __DIR__ . '/../../../../public/uploadapp.php';
        if (!is_file($target)) {
            error_log(sprintf('STUB MISSING: %s requires %s', __FILE__, $target));
            http_response_code(500);
            echo 'Service temporarily unavailable';
            return;
        }
        $out = (static function (string $file): string {
            ob_start();
            try {
                // TODO(2025): extract legacy block for manual conversion from public/uploadapp.php:1-320
                require $file;
            } catch (\Throwable $e) {
                error_log('Legacy stub error: ' . $e->getMessage());
            }
            return (string) ob_get_clean();
        })($target);

        echo $out;
    }
}
