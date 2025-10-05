<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Database;

final class OpHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T17:02:40Z via codex handler conversion
        try {
            global $container;

            AuthZ::requireRole('admin');

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            unset($config);

            /** @var Database $db */
            $db = $container->get(Database::class);
            unset($db);

            class_check(UC_MAX);

            require_once VENDOR_DIR . 'amnuts/opcache-gui/index.php';
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
