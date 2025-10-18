<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18 via handler-convert (offset=195 batch=5)

namespace PU239\Http\Handlers\PublicSite;

final class PortCheckHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18 via handler-convert (offset=195 batch=5)
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            $user = check_user_status();
            $stdfoot = [
                'js' => [
                    get_file_name('checkport_js'),
                ],
            ];

            if ($user['class'] >= UC_STAFF && !empty($_GET['id']) && is_valid_id((int) $_GET['id'])) {
                $id = (int) $_GET['id'];
            } else {
                $id = (int) $user['id'];
            }
            $username = format_username($id);

            $completed = "    <h1 class='has-text-centered'>" . _fe('Port Status for {0}', $username) . '</h1>';
            $completed .= main_div("    <div id='ipports' data-uid='{$id}' class='bg-04 round10'></div>
    <div class='columns top10 is-variable is-0-mobile is-1-tablet is-2-desktop padding20'>
        <div class='has-text-centered column is-one-third'>
            <input class='w-100' type='text' id='userip' placeholder='" . _fe('Your Torrent Client IP [{0}]', getip($id)) . "'>
        </div>
        <div class='has-text-centered column is-one-third'>
            <input class='w-100' type='text' id='userport' placeholder='" . _('Your Torrent Client Port') . "'>
        </div>
        <div class='has-text-centered column is-one-third'>
            <input class='w-100' type='text' id='ipport' placeholder='" . _('Check Status') . "' readonly>
        </div>
    </div>
    <div class='has-text-centered'>
        <input id='portcheck' type='submit' value='" . _('Test Connectivity') . "' class='button is-small margin20'>
    </div>");
            $title = _('Check My Ports');
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($completed) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
