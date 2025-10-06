<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=60-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Database;

final class RequestVoteHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=60-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();
            if ($user === false) {
                json_out(['voted' => 'invalid']);

                return;
            }

            // TODO(2025): csrf
            $requestId = (int) ($_POST['id'] ?? 0);
            $voted = $_POST['voted'] ?? null;

            if ($requestId <= 0 || $voted === null) {
                json_out(['voted' => 'invalid']);

                return;
            }

            $params = [
                'user_id' => (int) $user['id'],
                'request_id' => $requestId,
            ];

            if ($voted === 'yes') {
                $db->run(
                    'UPDATE request_votes SET vote = :vote WHERE user_id = :user_id AND request_id = :request_id',
                    $params + ['vote' => 'no'],
                );

                json_out(['voted' => 'no']);

                return;
            }

            if ($voted === 'no') {
                $db->run(
                    'DELETE FROM request_votes WHERE user_id = :user_id AND request_id = :request_id',
                    $params,
                );

                json_out(['voted' => 0]);

                return;
            }

            $db->run(
                'INSERT INTO request_votes (user_id, request_id, vote) VALUES (:user_id, :request_id, :vote)',
                $params + ['vote' => 'yes'],
            );

            json_out(['voted' => 'yes']);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
