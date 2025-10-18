<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:25:00Z via handler-convert offset=180 size=5

namespace PU239\Http\Handlers\Public;

final class SignupHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // TODO(2025): merge conflict blocks in public/signup.php:40-120 prevent safe extraction (argon hashing + promo flow)
        // STUB_UPGRADED: safe buffered execution
        $target = __DIR__ . '/../../../../public/signup.php';
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
