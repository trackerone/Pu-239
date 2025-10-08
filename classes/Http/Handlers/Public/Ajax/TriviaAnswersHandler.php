<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=105-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Cache;
use Pu239\Database;

final class TriviaAnswersHandler
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

            if ($user === false) {
                \json_out(['fail' => 'invalid']);

                return;
            }

            // TODO(2025): add CSRF verification
            $gameNumber = (int) ($_POST['gamenum'] ?? 0);
            $questionId = (int) ($_POST['qid'] ?? 0);
            $answer = (string) ($_POST['answer'] ?? '');
            $userId = (int) $user['id'];

            if ($gameNumber <= 0 || $questionId <= 0 || $answer === '') {
                \json_out(['fail' => 'invalid']);

                return;
            }

            $correctAnswer = $db->fetch(
                'SELECT canswer FROM triviaq WHERE qid = :qid',
                ['qid' => [$questionId, \PDO::PARAM_INT]],
            );

            if ($correctAnswer === false) {
                \json_out(['fail' => 'invalid']);

                return;
            }

            $existing = $db->fetch(
                'SELECT correct FROM triviausers WHERE user_id = :uid AND qid = :qid AND gamenum = :gamenum',
                [
                    'uid' => [$userId, \PDO::PARAM_INT],
                    'qid' => [$questionId, \PDO::PARAM_INT],
                    'gamenum' => [$gameNumber, \PDO::PARAM_INT],
                ],
            );

            if ($existing !== false) {
                $answered = (int) ($existing['correct'] ?? 0) === 1
                    ? "<h3 class='has-text-success top20'>" . \_('Awesome, that was the correct answer') . '</h3>'
                    : "<h3 class='has-text-danger top20'>" . \_('Sorry, that was not the correct answer') . '</h3>';
            } else {
                $values = [
                    'user_id' => [$userId, \PDO::PARAM_INT],
                    'gamenum' => [$gameNumber, \PDO::PARAM_INT],
                    'qid' => [$questionId, \PDO::PARAM_INT],
                    'date' => [\date('Y-m-d H:i:s'), \PDO::PARAM_STR],
                    'correct' => [$answer === ($correctAnswer['canswer'] ?? '') ? 1 : 0, \PDO::PARAM_INT],
                ];

                $answered = $values['correct'][0] === 1
                    ? "<h3 class='has-text-success top20'>" . \_('Awesome, that was the correct answer') . '</h3>'
                    : "<h3 class='has-text-danger top20'>" . \_('Sorry, that was not the correct answer') . '</h3>';

                $db->run(
                    'INSERT INTO triviausers (user_id, gamenum, qid, date, correct) VALUES (:user_id, :gamenum, :qid, :date, :correct)',
                    $values,
                );
            }

            $cache->delete('triviaq_');
            $table = \trivia_table();
            $cleanup = \trivia_time();

            \json_out([
                'content' => ($table['table'] ?? '') . $answered . \trivia_clocks(),
                'round' => $cleanup['round'] ?? 0,
                'game' => $cleanup['game'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
