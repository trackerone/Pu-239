<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16T04:02:33Z via handler-convert offset=155 size=5

namespace PU239\Http\Handlers\Public;

use PU239\Config\ConfigRepository;
use Pu239\Session;
use RuntimeException;

final class ViewSqlHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16T04:02:33Z via handler-convert offset=155 size=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            $stdfoot = [
                'js' => [
                    get_file_name('iframe_js'),
                ],
            ];

            $user = check_user_status();
            $baseUrl = (string) $config->get('paths.baseurl');
            $databaseName = (string) $config->get('db.database');

            if (empty($user) || !has_access($user['class'], UC_SYSOP, 'coder')) {
                /** @var Session $session */
                $session = $container->get(Session::class);
                $session->set('is-danger', 'You do not have access to that page.');
                write_log($user['username'] . ' has attempted to access Adminer');
                write_info($user['username'] . ' has attempted to access a Staff Page');
                header("Location: {$baseUrl}");
                app_halt('Exit called');
            }

            write_info($user['username'] . ' has accessed a Staff Page: Adminer');
            $title = _('Adminer');
            $html = "<iframe src='{$baseUrl}/ajax/view_sql.php?username={$user['username']}&db={$databaseName}' id='iframe_adminer' name='iframe_adminer' onload='resizeIframe(this)' class='iframe'></iframe>";
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
