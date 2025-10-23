<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;

global $container;
/** @var ContainerInterface $container */
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

final class UserhistoryHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-22T04:39:41Z; tool=codex-safe-handler-convert; rules=2025.10.22; commit=TO_BE_FILLED
        try {
            // TODO(2025): extract legacy block from public/userhistory.php:1-173 (multiple sql_query usages + forum/torrent history joins)
            $target = __DIR__ . '/../../../../public/userhistory.php';
            if (!is_file($target)) {
                error_log(sprintf('STUB MISSING: %s requires %s', __FILE__, $target));
                http_response_code(500);
                echo 'Service temporarily unavailable';
                return;
            }

            ob_start();
            try {
                // TODO(2025): extract legacy flow manually (nested includes/globals). Legacy: public/userhistory.php
                require $target;
            } catch (\Throwable $legacyException) {
                error_log('Legacy stub error: ' . $legacyException->getMessage());
            }
            echo (string) ob_get_clean();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
