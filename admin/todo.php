<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);
require_once INCL_DIR . 'function_html.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $container;

$parsedown = $container->get(Parsedown::class);
$markdown = file_get_contents(ROOT_DIR . 'TODO.md');
if (!empty($markdown)) {
    $content = "
    <h1 class='has-text-centered'>TODO</h1><div class='padding20 round10 bg-00'>" . $parsedown->parse($markdown) . '</div>';
    $HTMLOUT .= main_div($content, null, 'padding20');
} else {
    stderr(_('Error'), _('No content'));
}

$title = _('TODO Reader');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
