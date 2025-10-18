<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18 via handler-convert (offset=195 batch=5)

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Config\ConfigRepository;

final class ChatHandler
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

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            $user = check_user_status();
            $baseurl = (string) $config->get('paths.baseurl');

            $HTMLOUT = main_div("    <div class='padding20'>
    <p class='has-text-centered'>" . _fe('The official IRC channel is {0}#pu-239{1}', "<a href='irc://irc.p2p-network.net'>", '</a>') . '</p>'
            );

            $title = _('IRC');
            $breadcrumbs = [
                "<a href='{$baseurl}/chat.php'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT);
            echo stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
