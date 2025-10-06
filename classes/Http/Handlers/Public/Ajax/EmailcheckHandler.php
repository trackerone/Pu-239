<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=55-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Database;
use Pu239\User;

final class EmailcheckHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=55-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var Database $db */
            $db = $container->get(Database::class);

            if (empty($_GET['wantemail'])) {
                app_halt("<div class='margin10 has-text-info'>You can't post nothing please enter a email!</div>");
            }

            if (is_array($_GET['wantemail']) || !validemail($_GET['wantemail'])) {
                echo "<span class='has-text-danger'>" . _('Invalid Email Address') . '</span>';
                app_halt('Exit called');
            }

            /** @var User $user */
            $user = $container->get(User::class);
            if ($user->get_count_by_email(htmlsafechars($_GET['wantemail']))) {
                echo "<div class='has-text-danger tooltipper margin10' title='" . _('Email Not Available') . "'><i class='icon-thumbs-down icon' aria-hidden='true'></i>" . _fe('Email: {0} is unavailable.', format_comment($_GET['wantemail'])) . '</div>';
            } else {
                echo "<div class='has-text-success tooltipper margin10' title='" . _('Email Available') . "'><i class='icon-thumbs-up icon' aria-hidden='true'></i><b>" . _('Email Available') . '</b></div>';
            }
            app_halt('Exit called');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
