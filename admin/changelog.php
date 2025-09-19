<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Database;


global $container;

$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$parsedown = $container->get(Parsedown::class);
$markdown  = file_get_contents(ROOT_DIR . 'CHANGELOG.md');

if (!empty($markdown)) {
    $content = "
    <h1 class='has-text-centered'>CHANGELOG</h1><div class='padding20 round10 bg-00'>" . $parsedown->parse($markdown) . '</div>';
    $HTMLOUT .= main_div($content, null, 'padding20');
} else {
    stderr(_('Error'), 'No content');
}
$title = _('CHANGELOG Reader');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
