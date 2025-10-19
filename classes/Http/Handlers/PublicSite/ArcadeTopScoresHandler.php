<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19 via handler-convert offset=260 batch=5

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class ArcadeTopScoresHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19 via handler-convert offset=260 batch=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            $container = $GLOBALS['container'] ?? null;
            if ($container === null) {
                throw new \RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $siteName = (string) $config->get('site.name');
            $topScorePoints = (int) $config->get('arcade.top_score_points');
            $baseurl = (string) $config->get('paths.baseurl');
            $imagesBase = (string) $config->get('paths.images_baseurl');
            $gameNames = (array) $config->get('arcade.game_names');
            $games = (array) $config->get('arcade.games');

            $user = check_user_status();

            $scores = $db->fetchAll(
                'SELECT game, user_id, level, score FROM flashscores ORDER BY game, level DESC, score DESC'
            );
            $highscores = $db->fetchAll(
                'SELECT game, user_id, level, score FROM highscores ORDER BY game'
            );

            $getScores = static function (string $game, array $scoreRows): array {
                $filtered = [];
                foreach ($scoreRows as $scoreRow) {
                    if (($scoreRow['game'] ?? null) === $game) {
                        $filtered[] = $scoreRow;
                    }
                }

                return $filtered;
            };

            $getHighscore = static function (string $game, array $highscoreRows): ?array {
                foreach ($highscoreRows as $scoreRow) {
                    if (($scoreRow['game'] ?? null) === $game) {
                        return $scoreRow;
                    }
                }

                return null;
            };

            $list = $gameNames;
            sort($list);

            $html = "
        <h1 class='has-text-centered'>" . _fe('{0} Arcade Top Scores!', $siteName) . "</h1>
        <div class='bottom10 has-text-centered'>
            <div>" . _fe('Top Scores Earn {0} Karma Points', $topScorePoints) . "</div>
            <div class='level-center top10'>
                <a class='is-link' href='{$baseurl}/arcade.php'>" . _('Back to the Arcade') . '</a>
            </div>
        </div>';

            $tableHead = '
                    <tr>
                        <th>' . _('Rank') . '</th>
                        <th>' . _('Name') . '</th>
                        <th>' . _('Level') . '</th>
                        <th>' . _('Score') . '</th>
                    </tr>';

            foreach ($list as $gname) {
                $gameId = array_search($gname, $gameNames, true);
                if ($gameId === false) {
                    continue;
                }

                $gameKey = $games[$gameId] ?? '';
                if ($gameKey === '') {
                    continue;
                }

                $gameScores = $getScores($gameKey, $scores);
                if ($gameScores === []) {
                    continue;
                }

                $highscore = $getHighscore($gameKey, $highscores);
                if ($highscore === null) {
                    continue;
                }

                $body = '
                    <tr>
                        <td>0</td>
                        <td>' . format_username((int) $highscore['user_id']) . '</td>
                        <td>' . (int) $highscore['level'] . '</td>
                        <td>' . number_format((float) $highscore['score']) . '</td>
                    </tr>
                    <tr>
                        <td colspan="4"></td>
                    </tr>';

                $userHigh = 0.0;
                $userRank = 0;
                $rank = 0;
                foreach ($gameScores as $scoreRow) {
                    $rank++;
                    $body .= '
                    <tr>
                        <td>' . $rank . '</td>
                        <td>' . format_username((int) $scoreRow['user_id']) . '</td>
                        <td>' . (int) $scoreRow['level'] . '</td>
                        <td>' . number_format((float) $scoreRow['score']) . '</td>
                    </tr>';

                    if ((int) $scoreRow['user_id'] === (int) $user['id'] && $userHigh === 0.0) {
                        $userHigh = (float) $scoreRow['score'];
                        $userRank = $rank;
                    }

                    if ($rank >= 10) {
                        break;
                    }
                }

                if ($userHigh > 0.0) {
                    $body .= '
                    <tr>
                        <td colspan="4">
                            <div class="top10 bottom10 has-text-centered">' . _fe('Your high score was {0} and you ranked #{1}.', number_format($userHigh), number_format((float) $userRank)) . '</div>
                        </td>
                    </tr>';
                }

                $table = main_table($body, $tableHead, 'top20');

                $html .= "
        <div class='bg-00 round10 has-text-centered top20'>
            <a id='{$gameKey}'></a>
            <a href='{$baseurl}/flash.php?gameURI={$gameKey}.swf&amp;gamename={$gameKey}&amp;game_id={$gameId}'>
                <img src='{$imagesBase}games/{$gameKey}.png' alt='{$gname}' class='round10 top20 w-50 min-250'>
            </a>{$table}
        </div>";
            }

            $title = _('Top Scores');
            $breadcrumbs = [
                "<a href='{$baseurl}/games.php'>" . _('Games') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
