<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use RuntimeException;

global $container;
/** @var ContainerInterface $container */
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

final class UsermoodHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-22T04:39:41Z; tool=codex-safe-handler-convert; rules=2025.10.22; commit=TO_BE_FILLED
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                // TODO(2025): extract legacy flow manually (nested includes/globals). Legacy: public/index.php
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            throw new RuntimeException('Stubbed: missing SQL; see tools/rehydrate_v3_manifest.csv');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
