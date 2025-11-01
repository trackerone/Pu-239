<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use Psr\Container\ContainerInterface;

final class LoadController
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23; tool=codex-admin-medium-require; rules=2025.10.23-admin-require
        try {
            global $container;
            $container = $this->container;

            require_once __DIR__ . '/../../../admin/load.legacy.php';
        } catch (\Throwable $e) {
            error_log('Admin controller error (load): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}
