<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06, tools/handler_convert batch=45

namespace PU239\Http\Handlers\Admin;

final class AcpmanageHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // TODO(2025): extract legacy block from admin/acpmanage.php:1-200 (merge markers and DB transitions)
        $target = __DIR__ . '/../../../../admin/acpmanage.php';
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
