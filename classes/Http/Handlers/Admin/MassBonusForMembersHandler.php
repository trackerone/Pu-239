<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-21T00:05:00Z via handler-convert offset=335 batch=5

namespace PU239\Http\Handlers\Admin;

use function error_log;
use function http_response_code;
use function is_file;
use function ob_get_clean;
use function ob_start;
use function sprintf;

final class MassBonusForMembersHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-21T00:05:00Z via handler-convert offset=335 batch=5
        // TODO(2025): extract legacy block from admin/mass_bonus_for_members.php:1-360 (bulk rewards + messaging flow)

        $target = __DIR__ . '/../../../../admin/mass_bonus_for_members.php';
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
