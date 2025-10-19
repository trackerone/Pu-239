<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T16:48:06Z via handler-convert offset=285 batch=5

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Config\ConfigRepository;
use Pu239\Session;

use function dirname;
use function sprintf;

final class ViewSqlHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T16:48:06Z via handler-convert offset=285 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            $stdfoot = [
                'js' => [
                    get_file_name('iframe_js'),
                ],
            ];

            global $container;

            $user = check_user_status();

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $baseUrl = (string) $config->get('paths.baseurl');
            $databaseName = (string) $config->get('db.database');

            if (empty($user) || !has_access($user['class'] ?? 0, UC_SYSOP, 'coder')) {
                /** @var Session $session */
                $session = $container->get(Session::class);
                $session->set('is-danger', 'You do not have access to that page.');
                write_log(($user['username'] ?? 'unknown') . ' has attempted to access Adminer');
                write_info(($user['username'] ?? 'unknown') . ' has attempted to access a Staff Page');
                header('Location: ' . $baseUrl);
                app_halt('Exit called');
            }

            write_info(($user['username'] ?? 'unknown') . ' has accessed a Staff Page: Adminer');

            $html = "<iframe src='{$baseUrl}/ajax/view_sql.php?username={$user['username']}&db={$databaseName}' id='iframe_adminer' name='iframe_adminer' onload='resizeIframe(this)' class='iframe'></iframe>";

            $title = _('Adminer');
            $breadcrumbs = [
                sprintf("<a href='%s'>%s</a>", $_SERVER['PHP_SELF'] ?? '', $title),
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
