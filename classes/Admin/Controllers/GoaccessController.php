<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Database;
use Psr\Container\ContainerInterface;

final class GoaccessController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
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

            $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? '';
            if (strpos($scriptPath, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $db = $container->get(Database::class);

            if (file_exists(CACHE_DIR . 'goaccess.html')) {
                require_once CACHE_DIR . 'goaccess.html';
                app_halt('Exit called');
            } else {
                stderr(_('Error'), 'Is goaccess installed?');
            }

            $title = _('GoAccess');
            $breadcrumbs = [
                "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Admin controller error (goaccess): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}
