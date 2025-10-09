<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-09 via handler-convert batch=110-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\User;

final class UsersearchHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-09 via handler-convert batch=110-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var User $userRepo */
            $userRepo = $container->get(User::class);

            check_user_status();

            // TODO(2025): add CSRF verification
            $keyword = trim((string) ($_POST['keyword'] ?? ''));

            if ($keyword === '') {
                json_out(['data' => _('Invalid Request')]);
            }

            $users = $userRepo->search_by_username(strtolower($keyword));

            if (!empty($users)) {
                json_out($users);
            }

            json_out(['data' => _('Invalid Request')]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
