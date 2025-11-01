<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use Psr\Container\ContainerInterface;
use PU239\Config\ConfigRepository;
use PDO;

use Pu239\Database;
use PU239\Security\AuthZ;

final class BanclientController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
        private readonly PDO $pdo,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23; tool=codex-admin-medium-require; rules=2025.10.23-admin-require
        try {
            global $container;
            $container = $this->container;
            $config = $this->config;
            $pdo = $this->pdo;

            // TODO(2025): inline legacy logic from admin/banclient.php (was using legacy require)

            if (strpos(__FILE__, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            global $container;
            /** @var ContainerInterface $container */
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            // AUTO_ADMIN_MEDIUM: 2025-10-23; tool=codex-admin-medium-sweep; rules=2025.10.23-admin-medium

            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $HTMLOUT = "
                <h1 class='has-text-centered'>Not Implemented Yet</h1>";

            $title = _('Ban Torrent Clients');
            $breadcrumbs = [
                "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Admin controller error (banclient): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}
