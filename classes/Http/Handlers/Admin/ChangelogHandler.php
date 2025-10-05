<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T17:02:40Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use Parsedown;
use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;

final class ChangelogHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T17:02:40Z via codex handler conversion
        try {
            global $container;

            if (strpos(ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            /** @var Parsedown $parsedown */
            $parsedown = $container->get(Parsedown::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $markdownPath = ROOT_DIR . 'CHANGELOG.md';
            $markdown = is_file($markdownPath) ? (string) file_get_contents($markdownPath) : '';
            if ($markdown === '') {
                stderr(_('Error'), 'No content');
            }

            $content = "
    <h1 class='has-text-centered'>CHANGELOG</h1><div class='padding20 round10 bg-00'>" . $parsedown->parse($markdown) . '</div>';
            $HTMLOUT = '';
            $HTMLOUT .= main_div($content, null, 'padding20');

            $title = _('CHANGELOG Reader');
            $baseurl = (string) $config->get('paths.baseurl');
            $self = $_SERVER['PHP_SELF'] ?? '';
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
