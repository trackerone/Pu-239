<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use PU239\Security\AuthZ;
use Pu239\Database;

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
