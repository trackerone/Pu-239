<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-22T03:48:59Z via handler-convert offset=350 batch=5

namespace PU239\Http\Handlers\Public;

use RuntimeException;

use function defined;
use function dirname;
use function error_log;
use function http_response_code;

final class StaffboxHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-22T03:48:59Z via handler-convert offset=350 batch=5
        try {
            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            // TODO(2025): restore staffbox workflow once public/staffbox.php SQL is rehydrated.
            throw new RuntimeException('Stubbed: missing SQL; see tools/rehydrate_v3_manifest.csv');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
