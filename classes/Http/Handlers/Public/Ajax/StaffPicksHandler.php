<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=105-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Cache;
use Pu239\Database;

final class StaffPicksHandler
{
    /** @param array<string, mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=105-5
        try {
            global $container;

            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = \check_user_status();
            if ($user === false || ($user['class'] ?? 0) < UC_STAFF) {
                \json_out(['pick' => 'class']);

                return;
            }

            // TODO(2025): csrf on POST where missing
            $pick = (int) ($_POST['pick'] ?? -1);
            $torrentId = (int) ($_POST['id'] ?? 0);

            if ($pick < 0 || $torrentId <= 0) {
                \json_out(['pick' => 'invalid']);

                return;
            }

            $newValue = $pick === 0 ? TIME_NOW : 0;

            $statement = $db->run(
                'UPDATE torrents SET staff_picks = :staff_picks WHERE id = :id',
                [
                    'staff_picks' => [$newValue, \PDO::PARAM_INT],
                    'id' => [$torrentId, \PDO::PARAM_INT],
                ],
            );

            if ($statement->rowCount() > 0) {
                $operation = $pick === 0 ? 'staff_picks.enable' : 'staff_picks.disable';
                \audit_log(
                    $user['id'] ?? null,
                    'torrent.moderate',
                    [
                        'id' => $torrentId,
                        'op' => $operation,
                    ],
                );

                $cache->delete('staff_picks_');

                \json_out(['pick' => $newValue]);

                return;
            }

            \json_out(['pick' => 'fail']);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
