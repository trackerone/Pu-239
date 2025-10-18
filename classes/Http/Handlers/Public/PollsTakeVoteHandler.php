<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:24:28Z via handler-convert offset=205 size=5

namespace PU239\Http\Handlers\Public;

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\PollVoter;
use RuntimeException;

final class PollsTakeVoteHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:24:28Z via handler-convert offset=205 size=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var PollVoter $pollVoter */
            $pollVoter = $container->get(PollVoter::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $pollDataTtl = (int) $config->get('expires.poll_data', 0);
            $baseUrl = (string) $config->get('paths.baseurl');

            $user = check_user_status();

            $pollId = (int) ($_GET['pollid'] ?? 0);
            if (!is_valid_id($pollId)) {
                stderr(_('Error'), 'No poll with that ID');
            }

            $choicesInput = $_POST['choice'] ?? [];
            if (!is_array($choicesInput)) {
                $choicesInput = [];
            }

            // TODO(2025): add CSRF verification

            $pollData = $db->row(
                'SELECT p.*, pv.user_id AS voter_id
                 FROM polls AS p
                 LEFT JOIN poll_voters AS pv ON p.pid = pv.poll_id AND pv.user_id = :user_id
                 WHERE p.pid = :poll_id',
                [
                    ':user_id' => [$user['id'], \PDO::PARAM_INT],
                    ':poll_id' => [$pollId, \PDO::PARAM_INT],
                ],
            );

            if (empty($pollData)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            if (!empty($pollData['voter_id'])) {
                stderr(_('Error'), _('You have already voted!'));
            }

            $nullVote = isset($_POST['nullvote']) ? (int) $_POST['nullvote'] : 0;
            $voteCast = [];

            if (!$nullVote) {
                foreach ($choicesInput as $questionId => $choiceId) {
                    $question = (int) $questionId;
                    if ($question <= 0) {
                        continue;
                    }

                    $selectedChoices = is_array($choiceId) ? $choiceId : [$choiceId];
                    foreach ($selectedChoices as $selectedChoice) {
                        $voteCast[$question][] = (int) $selectedChoice;
                    }

                    if (isset($voteCast[$question])) {
                        $voteCast[$question] = array_values(array_filter(
                            array_unique(array_map('intval', (array) $voteCast[$question])),
                            static fn(int $value): bool => $value >= 0,
                        ));
                    }
                }

                foreach ($_POST as $key => $value) {
                    if (!is_scalar($value)) {
                        continue;
                    }
                    if (preg_match("#^choice_(\\d+)_(\\d+)$#", (string) $key, $matches) === 1 && (int) $value === 1) {
                        $questionId = (int) $matches[1];
                        $choiceId = (int) $matches[2];
                        $voteCast[$questionId][] = $choiceId;
                        $voteCast[$questionId] = array_values(array_filter(
                            array_unique(array_map('intval', (array) $voteCast[$questionId])),
                            static fn(int $val): bool => $val >= 0,
                        ));
                    }
                }
            }

            $pollAnswers = [];
            if (!empty($pollData['choices'])) {
                $decoded = json_decode((string) $pollData['choices'], true);
                if (is_array($decoded)) {
                    $pollAnswers = $decoded;
                }
            }

            if (!$nullVote && !empty($pollAnswers) && count($voteCast) < count($pollAnswers)) {
                stderr(_('Error'), 'No vote');
            }

            $values = [
                'user_id' => $user['id'],
                'poll_id' => $pollData['pid'],
                'vote_date' => TIME_NOW,
            ];
            $pollVoteId = $pollVoter->add($values);
            if (!$pollVoteId) {
                stderr(_('Error'), _('Could not update records'));
            }

            $votes = (int) ($pollData['votes'] ?? 0) + 1;
            if (!$nullVote) {
                foreach ($voteCast as $questionId => $choiceArray) {
                    foreach ($choiceArray as $choiceId) {
                        if (!is_numeric($choiceId)) {
                            continue;
                        }
                        $choiceKey = (int) $choiceId;
                        if (!isset($pollAnswers[$questionId]['votes'][$choiceKey])) {
                            $pollAnswers[$questionId]['votes'][$choiceKey] = 0;
                        }
                        ++$pollAnswers[$questionId]['votes'][$choiceKey];
                        if ($pollAnswers[$questionId]['votes'][$choiceKey] < 1) {
                            $pollAnswers[$questionId]['votes'][$choiceKey] = 1;
                        }
                    }
                }

                $choicesJson = json_encode($pollAnswers, JSON_THROW_ON_ERROR);
                $db->run(
                    'UPDATE polls SET votes = votes + 1, choices = :choices WHERE pid = :pid',
                    [
                        ':choices' => $choicesJson,
                        ':pid' => [$pollData['pid'], \PDO::PARAM_INT],
                    ],
                );

                $cache->update_row('poll_data_' . $user['id'], [
                    'votes' => $votes,
                    'user_id' => $user['id'],
                    'vote_date' => TIME_NOW,
                    'choices' => $choicesJson,
                ], $pollDataTtl);
            } else {
                $db->run('UPDATE polls SET votes = votes + 1 WHERE pid = :pid', [':pid' => [$pollData['pid'], \PDO::PARAM_INT]]);
                $cache->update_row('poll_data_' . $user['id'], [
                    'votes' => $votes,
                    'user_id' => $user['id'],
                    'vote_date' => TIME_NOW,
                ], $pollDataTtl);
            }

            header('Location: ' . $baseUrl . '/#poll');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
