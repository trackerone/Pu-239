<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Admin;

use Parsedown;
use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;

final class TodoHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05 via tools/handler_convert_report.csv
        try {
            if (strpos(__FILE__, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            /** @var Parsedown $parsedown */
            $parsedown = $container->get(Parsedown::class);
            $markdown = file_get_contents(ROOT_DIR . 'TODO.md') ?: '';

            $htmlOut = '';
            if ($markdown !== '') {
                $content = "
    <h1 class='has-text-centered'>TODO</h1><div class='padding20 round10 bg-00'>" . $parsedown->parse($markdown) . '</div>';
                $htmlOut .= main_div($content, null, 'padding20');
            } else {
                stderr(_('Error'), _('No content'));
            }

            $title = _('TODO Reader');
            $breadcrumbs = [
                "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='" . ($_SERVER['PHP_SELF'] ?? '') . "'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
