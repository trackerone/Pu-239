<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=105-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Cache;
use Pu239\Database;

final class TriviaLookupHandler
{
    /** @param array<string, mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=105-5
        try {
            global $container;

            /** @var Database $db */
            $db = $container->get(Database::class);

            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $curuser = \check_user_status();

            if (empty($curuser)) {
                \json_out(['fail' => 'csrf']);

                return;
            }

            $table = \trivia_table();
            $qid = $table['qid'];
            $gamenum = $table['gamenum'];
            $tableContent = $table['table'];
            $data = $cache->get('trivia_current_question_');

            if (empty($data)) {
                \json_out(['fail' => 'invalid']);

                return;
            }

            // $fluent removed — use $this->db (ExtendedPdo)
            $user = $db->row(
                'SELECT correct FROM triviausers WHERE user_id = :uid AND qid = :qid AND gamenum = :gamenum',
                [
                    'uid' => [$curuser['id'], \PDO::PARAM_INT],
                    'qid' => [$qid, \PDO::PARAM_INT],
                    'gamenum' => [$gamenum, \PDO::PARAM_INT],
                ],
            );

            $cleanup = \trivia_time();
            if (!empty($user)) {
                if ((int) ($user['correct'] ?? 0) === 1) {
                    $answered = "<h3 class='has-text-success top20'>" . \_('Awesome, that was the correct answer') . '</h3>';
                } else {
                    $answered = "<h3 class='has-text-danger top20'>" . \_('Sorry, that was not the correct answer') . '</h3>';
                }
                \json_out([
                    'content' => $tableContent . $answered . \trivia_clocks(),
                    'round' => $cleanup['round'],
                    'game' => $cleanup['game'],
                ]);

                return;
            }

            $question = $output = '';
            $answers = [
                'answer1',
                'answer2',
                'answer3',
                'answer4',
                'answer5',
            ];
            if (!empty($data['question'])) {
                $question = "
        <h2 class='bg-00 padding10 bottom10 round5'>" . \format_comment($data['question']) . '</h2>';
            }
            foreach ($answers as $answer) {
                if (!empty($data[$answer])) {
                    $output .= "
        <span id='{$answer}' class='size_4 margin10 trivia-pointer bg-00 round5 padding10' data-answer='{$answer}'  data-qid='{$qid}' data-gamenum='{$gamenum}' onclick=\"process_trivia('$answer')\">" . \format_comment($data[$answer]) . '</span>';
                }
            }
            if (!empty($output)) {
                $output = "<div class='level-center'>$output</div>";
                \json_out([
                    'content' => $question . $output . \trivia_clocks(),
                    'round' => $cleanup['round'],
                    'game' => $cleanup['game'],
                ]);

                return;
            }

            \json_out(['fail' => 'invalid']);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
