<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=55-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Database;

final class CookerNotifyHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=55-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();

            if ($user === false) {
                json_out(['notify' => 'invalid']);
            }

            // TODO(2025): csrf
            $upcomingId = (int) ($_POST['id'] ?? 0);
            $notified = filter_var($_POST['notified'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($upcomingId <= 0 || $notified === null) {
                json_out(['notify' => 'invalid']);
            }

            $params = [
                'userid' => (int) $user['id'],
                'upcomingid' => $upcomingId,
            ];

            if ($notified) {
                $db->run(
                    'DELETE FROM upcoming_notify WHERE userid = :userid AND upcomingid = :upcomingid',
                    $params
                );

                json_out(['notify' => 0]);
            }

            $db->run(
                'INSERT INTO upcoming_notify (userid, upcomingid, added) VALUES (:userid, :upcomingid, :added)',
                $params + ['added' => [TIME_NOW, \PDO::PARAM_INT]]
            );

            $insertId = (int) $db->fetchValue('SELECT LAST_INSERT_ID()');

            json_out(['notify' => $insertId]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
