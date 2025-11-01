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

$parsedown = $container->get(Parsedown::class);
$markdown  = file_get_contents(ROOT_DIR . 'TODO.md');

if (!empty($markdown)) {
    $content = "
    <h1 class='has-text-centered'>TODO</h1><div class='padding20 round10 bg-00'>" . $parsedown->parse($markdown) . '</div>';
    $HTMLOUT .= main_div($content, null, 'padding20');
} else {
    stderr(_('Error'), _('No content'));
}

$title = _('TODO Reader');
$breadcrumbs = [
    "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
