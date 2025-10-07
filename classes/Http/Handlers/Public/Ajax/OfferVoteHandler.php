<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=60-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Database;

final class OfferVoteHandler
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

            // TODO(2025): csrf on POST where missing
            $offerId = (int) ($_POST['id'] ?? 0);
            $currentVote = (string) ($_POST['voted'] ?? '');

            if ($offerId <= 0 || $currentVote === '') {
                json_out(['voted' => 'invalid']);

                return;
            }

            $params = [
                'offer_id' => $offerId,
                'user_id' => (int) $user['id'],
            ];

            if ($currentVote === 'yes') {
                $db->run(
                    'UPDATE offer_votes SET vote = :vote WHERE offer_id = :offer_id AND user_id = :user_id',
                    $params + ['vote' => 'no'],
                );

                json_out(['voted' => 'no']);

                return;
            }

            if ($currentVote === 'no') {
                $db->run(
                    'DELETE FROM offer_votes WHERE offer_id = :offer_id AND user_id = :user_id',
                    $params,
                );

                json_out(['voted' => 0]);

                return;
            }

            $db->run(
                'INSERT INTO offer_votes (vote, user_id, offer_id, added) VALUES (:vote, :user_id, :offer_id, :added)',
                $params + [
                    'vote' => 'yes',
                    'added' => [TIME_NOW, \PDO::PARAM_INT],
                ],
            );

            json_out(['voted' => 'yes']);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
