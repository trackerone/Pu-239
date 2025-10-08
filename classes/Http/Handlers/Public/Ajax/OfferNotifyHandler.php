<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Public\Ajax;

use PU239\Config\ConfigRepository;
use Pu239\Database;

final class OfferNotifyHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08T04:13:01Z via codex handler conversion
        try {
            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            unset($config);

            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = \check_user_status();
            if ($user === false) {
                \json_out(['notify' => 'invalid']);
                return;
            }

            // TODO(2025): csrf on POST where missing
            $offerId = (int) ($_POST['id'] ?? 0);
            $notified = \filter_var($_POST['notified'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($offerId <= 0 || $notified === null) {
                \json_out(['notify' => 'invalid']);
                return;
            }

            $params = [
                'userid' => (int) $user['id'],
                'offerid' => $offerId,
            ];

            if ($notified) {
                $db->run(
                    'DELETE FROM offer_notify WHERE userid = :userid AND offerid = :offerid',
                    $params,
                );

                \json_out(['notify' => 0]);
                return;
            }

            $db->run(
                'INSERT INTO offer_notify (userid, offerid, added) VALUES (:userid, :offerid, :added)',
                $params + ['added' => [TIME_NOW, \PDO::PARAM_INT]],
            );

            $insertId = (int) $db->fetchValue('SELECT LAST_INSERT_ID()');

            \json_out(['notify' => $insertId]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
