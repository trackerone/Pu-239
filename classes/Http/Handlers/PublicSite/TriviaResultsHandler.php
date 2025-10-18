<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T19:27:35Z via handler_convert (batch=225-229)

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Database;
use RuntimeException;

final class TriviaResultsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T19:27:35Z via handler_convert (batch=225-229)
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var Database $db */
            $db = $container->get(Database::class);

            check_user_status();

            $games = $db->fetchAll(
                'SELECT gamenum, IFNULL(UNIX_TIMESTAMP(finished), 0) AS ended, IFNULL(UNIX_TIMESTAMP(started), 0) AS started
                    FROM triviasettings
                    GROUP BY gamenum, finished, started
                    ORDER BY gamenum DESC
                    LIMIT :limit',
                [
                    ':limit' => [10, \PDO::PARAM_INT],
                ],
            );

            $div = '';
            foreach ($games as $result) {
                $gameNumber = (int) ($result['gamenum'] ?? 0);
                $ended = (int) ($result['ended'] ?? 0);
                $started = (int) ($result['started'] ?? 0);

                $players = $db->fetchAll(
                    'SELECT t.gamenum, t.user_id, COUNT(t.correct) AS correct,
                            (
                                SELECT COUNT(correct)
                                FROM triviausers
                                WHERE correct = 0 AND user_id = t.user_id AND gamenum = :gamenum
                            ) AS incorrect,
                            u.username, u.modcomment
                        FROM triviausers AS t
                        INNER JOIN users AS u ON u.id = t.user_id
                        INNER JOIN triviasettings AS s ON s.gamenum = t.gamenum
                        WHERE t.correct = 1 AND t.gamenum = :gamenum
                        GROUP BY t.user_id
                        ORDER BY correct DESC, incorrect
                        LIMIT :limit',
                    [
                        ':gamenum' => [$gameNumber, \PDO::PARAM_INT],
                        ':limit' => [10, \PDO::PARAM_INT],
                    ],
                );

                if ($players === []) {
                    continue;
                }

                $dateLabel = $ended >= 1 ? 'Ended: ' . get_date($ended, 'LONG') : 'Started: ' . get_date($started, 'LONG');
                $div .= "
                <div class='bg-02 has-text-centered top20 round5'>
                    <div class='padtop20'>
                        <h1>Game #{$gameNumber} {$dateLabel}</h1>
                    </div>
                    <table class='table table-bordered table-striped'>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Ratio</th>
                                <th>Correct</th>
                                <th>Incorrect</th>
                            </tr>
                        </thead>
                        <tbody>";

                foreach ($players as $player) {
                    $correct = (int) ($player['correct'] ?? 0);
                    $incorrect = (int) ($player['incorrect'] ?? 0);
                    $total = $correct + $incorrect;
                    $ratio = $total > 0 ? sprintf('%.2f%%', ($correct / $total) * 100) : '0.00%';
                    $div .= '                        <tr>
                            <td>' . format_username((int) $player['user_id']) . '</td>
                            <td>' . $ratio . '</td>
                            <td>' . $correct . '</td>
                            <td>' . $incorrect . '</td>
                        </tr>';
                }

                $div .= '                        </tbody>
                    </table>
                </div>';
            }

            if ($div === '') {
                $div = main_div('No Trivia Results', 'has-text-centered', 'padding20');
            }

            $table = "
            <div class='portlet'>" . $div . '
            </div>';

            $title = _('Trivia');
            $breadcrumbs = [
                sprintf("<a href='%s'>%s</a>", htmlsafechars($_SERVER['PHP_SELF'] ?? ''), $title),
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($table) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
