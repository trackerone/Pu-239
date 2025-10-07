<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=70-5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class ArcadeTopScoresHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=70-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $siteName = (string) $config->get('site.name');
            $topScorePoints = (int) $config->get('arcade.top_score_points');
            $baseUrl = (string) $config->get('paths.baseurl');
            $imagesBase = (string) $config->get('paths.images_baseurl');
            $gameNames = (array) $config->get('arcade.game_names');
            $games = (array) $config->get('arcade.games');

            $user = check_user_status();

            $scores = $db->fetchAll(
                'SELECT game, user_id, level, score FROM flashscores ORDER BY game, level DESC, score DESC',
            );
            $highscores = $db->fetchAll(
                'SELECT game, user_id, level, score FROM highscores ORDER BY game',
            );

            $htmlOut = "
                <h1 class='has-text-centered'>" . _fe('{0} Arcade Top Scores!', $siteName) . "</h1>
                <div class='bottom10 has-text-centered'>
                    <div>" . _fe('Top Scores Earn {0} Karma Points', $topScorePoints) . "</div>
                    <div class='level-center top10'>
                        <a class='is-link' href='{$baseUrl}/arcade.php'>" . _('Back to the Arcade') . '</a>
                    </div>
                </div>';

            $list = $gameNames;
            sort($list);

            $heading = '
                            <tr>
                                <th>' . _('Rank') . '</th>
                                <th>' . _('Name') . '</th>
                                <th>' . _('Level') . '</th>
                                <th>' . _('Score') . '</th>
                            </tr>';

            foreach ($list as $gameName) {
                $gameId = array_search($gameName, $gameNames, true);
                if ($gameId === false) {
                    continue;
                }

                $game = (string) ($games[$gameId] ?? '');
                if ($game === '') {
                    continue;
                }

                $gameScores = $this->filterScores($game, $scores);
                if ($gameScores === []) {
                    continue;
                }

                $body = '';
                $highscore = $this->findHighscore($game, $highscores);
                if ($highscore !== null) {
                    $body .= '
                                <tr>
                                    <td>0</td>
                                    <td>' . format_username($highscore['user_id']) . '</td>
                                    <td>' . $highscore['level'] . '</td>
                                    <td>' . number_format((float) $highscore['score']) . '</td>
                                </tr>
                                <tr>
                                    <td colspan="4"></td>
                                </tr>';
                }

                $i = 0;
                $userHigh = 0.0;
                $userRank = 0;

                foreach ($gameScores as $scoreRow) {
                    $body .= '
                                <tr>
                                    <td>' . ++$i . '</td>
                                    <td>' . format_username($scoreRow['user_id']) . '</td>
                                    <td>' . $scoreRow['level'] . '</td>
                                    <td>' . number_format((float) $scoreRow['score']) . '</td>
                                </tr>';

                    if ($scoreRow['user_id'] === ($user['id'] ?? null) && $userHigh === 0.0) {
                        $userHigh = (float) $scoreRow['score'];
                        $userRank = $i;
                    }

                    if ($i >= 10) {
                        break;
                    }
                }

                if ($userHigh !== 0.0) {
                    $body .= '
                                <tr>
                                    <td colspan="4">
                                        <div class="top10 bottom10 has-text-centered">' . _fe('Your high score was {0} and you ranked #{1}.', number_format($userHigh), number_format((float) $userRank)) . '</div>
                                    </td>
                                </tr>';
                }

                $table = main_table($body, $heading, 'top20');
                $htmlOut .= "
                <div class='bg-00 round10 has-text-centered top20'>
                    <a id='{$game}'></a>
                    <a href='{$baseUrl}/flash.php?gameURI={$game}.swf&amp;gamename={$game}&amp;game_id={$gameId}'>
                        <img src='{$imagesBase}games/{$game}.png' alt='{$gameName}' class='round10 top20 w-50 min-250'>
                    </a>{$table}
                </div>";
            }

            $title = _('Top Scores');
            $breadcrumbs = [
                "<a href='{$baseUrl}/games.php'>" . _('Games') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    /**
     * @param array<int,array<string,mixed>> $scores
     * @return array<int,array<string,mixed>>
     */
    private function filterScores(string $game, array $scores): array
    {
        $gameScores = [];
        foreach ($scores as $score) {
            if (($score['game'] ?? null) === $game) {
                $gameScores[] = $score;
            }
        }

        return $gameScores;
    }

    /**
     * @param array<int,array<string,mixed>> $highscores
     * @return array<string,mixed>|null
     */
    private function findHighscore(string $game, array $highscores): ?array
    {
        foreach ($highscores as $score) {
            if (($score['game'] ?? null) === $game) {
                return $score;
            }
        }

        return null;
    }
}
