<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=60-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Database;

final class OfferStatusHandler
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
            if ($user === false || !has_access($user['class'], UC_STAFF, '')) {
                json_out(['status' => 'invalid']);

                return;
            }

            // TODO(2025): csrf on POST where missing
            $offerId = (int) ($_POST['id'] ?? 0);
            $currentStatus = (string) ($_POST['status'] ?? '');

            if ($offerId <= 0 || $currentStatus === '') {
                json_out(['status' => 'invalid']);

                return;
            }

            $nextStatus = match ($currentStatus) {
                'pending' => 'approved',
                'approved' => 'denied',
                default => 'pending',
            };

            $db->run(
                'UPDATE offers SET status = :status WHERE id = :id',
                [
                    'status' => $nextStatus,
                    'id' => $offerId,
                ],
            );

            audit_log(
                $user['id'] ?? null,
                'torrent.moderate',
                [
                    'id' => $offerId,
                    'op' => 'offer.status',
                    'from' => $currentStatus,
                    'to' => $nextStatus,
                ],
            );

            json_out(['status' => $nextStatus]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
