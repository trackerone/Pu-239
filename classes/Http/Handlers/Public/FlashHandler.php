<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16T03:48:50Z via handler-convert offset=150 size=5

namespace PU239\Http\Handlers\Public;

use PU239\Config\ConfigRepository;
use Pu239\Database;
use RuntimeException;

final class FlashHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16T03:48:50Z via handler-convert offset=150 size=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $baseUrl = (string) $config->get('paths.baseurl');
            $siteName = (string) $config->get('site.name');
            $arcadeGames = $config->arr('arcade.games');
            $arcadeGameNames = $config->arr('arcade.game_names');
            $topScorePoints = $config->get('arcade.top_score_points');

            $user = check_user_status();
            $scores = '';
            $player = $user['id'];

            $allOurGames = $arcadeGames;
            $gamename = '';
            if (isset($_GET['gamename'])) {
                $gamename = strip_tags((string) $_GET['gamename']);
                if (!in_array($gamename, $allOurGames, true)) {
                    stderr(_('Error'), _fe('No game with that name! ({0})', $gamename));
                }
            }

            $gameURI = '';
            if (isset($_GET['gameURI'])) {
                $gameURI = strip_tags((string) $_GET['gameURI']);
                $gameURIclean = str_replace('.swf', '', $gameURI);
                if (!in_array($gameURIclean, $allOurGames, true)) {
                    stderr(_('Error'), _('Could not find game!'));
                }
            }

            $game_name = str_replace('_', ' ', $gamename);
            $game_height = (!isset($user['gameheight']) || $user['gameheight'] === 0) ? 800 : (int) $user['gameheight'];
            $game_width = $game_height;

            $HTMLOUT = "
                    <div class='bottom20'>
                        <ul class='level-center bg-06'>
                            <li class='is-link margin10'>
                                <a href='{$baseUrl}/arcade.php'>" . _('Arcade') . "</a>
                            </li>
                            <li class='is-link margin10'>
                                <a href='{$baseUrl}/arcade_top_scores.php'>" . _('Top Scores') . "</a>
                            </li>
                        </ul>
                    </div>
                    <h1 class='has-text-centered'>" . _fe('{0} Old School Arcade!', $siteName) . "</h1>
                    <div class='has-text-centered'>" . _fe('Top Scores Earn {0} Karma Points', $topScorePoints) . '</div>';

            $HTMLOUT .= "
                    <div class='bordered top20'>
                        <div class='alt_bordered bg-00 has-text-centered'>
                            <object style='width: {$game_width}px; height: {$game_height}px;' width='{$game_width}' height='{$game_height}'>
                                <param name='movie' value='./media/flash_games/{$gameURI}'>
                                <param name='quality' value='high'>
                                <embed src='{$baseUrl}/media/flash_games/{$gameURI}' quality='high' type='application/x-shockwave-flash' style='width: {$game_width}px;' height='{$game_height}px;' width='{$game_width}' height='{$game_height}'>
                            </object>
                        </div>
                    </div>";

            $flashScores = $db->toArray(
                'SELECT id, user_id, score, level FROM flashscores WHERE game = :game ORDER BY score DESC LIMIT 15',
                [
                    'game' => $gamename,
                ],
            );

            if ($flashScores !== []) {
                $gameIndex = array_search($gamename, $arcadeGames, true);
                $fullgamename = $arcadeGameNames[$gameIndex] ?? '';
                $HTMLOUT .= "
                    <table class='table table-bordered table-striped top20 bottom20'>
                        <thead>
                            <tr>
                                <th colspan='4'>
                                    <div class='size_4 has-text-centered'>
                                        $fullgamename
                                    </div>
                                </th>
                            </tr>
                            <tr>
                                <th>" . _('Rank') . '</th>
                                <th>' . _('Name') . '</th>
                                <th>' . _('Level') . '</th>
                                <th>' . _('Score') . '</th>
                            </tr>
                        </thead>
                        <tbody>';

                $highScores = $db->toArray(
                    'SELECT id, user_id, score, level FROM highscores WHERE game = :game ORDER BY score DESC LIMIT 15',
                    [
                        'game' => $gamename,
                    ],
                );

                foreach ($highScores as $atScore) {
                    $higherCount = (int) ($db->fetchValue(
                        'SELECT COUNT(id) FROM highscores WHERE game = :game AND score > :score',
                        [
                            'game' => $gamename,
                            'score' => $atScore['score'],
                        ],
                    ) ?? 0);
                    $rank = $higherCount + 1;
                    $HTMLOUT .= '
                            <tr' . ($atScore['user_id'] == $user['id'] ? " class=\"has-text-primary text-shadow\"" : '') . '>
                                <td>' . number_format($rank) . '</td>
                                <td>' . format_username((int) $atScore['user_id']) . '</td>
                                <td>' . (int) $atScore['level'] . '</td>
                                <td>' . number_format((float) $atScore['score']) . '</td>
                            </tr>';
                }

                $lastFlashScoreRow = null;
                foreach ($flashScores as $flashScore) {
                    $higherFlashCount = (int) ($db->fetchValue(
                        'SELECT COUNT(id) FROM flashscores WHERE game = :game AND score > :score',
                        [
                            'game' => $gamename,
                            'score' => $flashScore['score'],
                        ],
                    ) ?? 0);
                    $HTMLOUT .= '
                            <tr' . ($flashScore['user_id'] == $player ? " class=\"has-text-primary text-shadow\"" : '') . '>
                                <td>' . number_format($higherFlashCount + 1) . '</td>
                                <td>' . format_username((int) $flashScore['user_id']) . '</td>
                                <td>' . (int) $flashScore['level'] . '</td>
                                <td>' . number_format((float) $flashScore['score']) . '</td>
                            </tr>';
                    $lastFlashScoreRow = $flashScore;
                }

                $memberScore = $db->row(
                    'SELECT id, user_id, score, level FROM flashscores WHERE game = :game AND user_id = :user ORDER BY score DESC LIMIT 1',
                    [
                        'game' => $gamename,
                        'user' => $user['id'],
                    ],
                );

                if ($memberScore !== null) {
                    $memberHigherCount = (int) ($db->fetchValue(
                        'SELECT COUNT(id) FROM flashscores WHERE game = :game AND score > :score',
                        [
                            'game' => $gamename,
                            'score' => $memberScore['score'],
                        ],
                    ) ?? 0);
                    $memberRank = $memberHigherCount + 1;
                    if ($memberRank > 10) {
                        $memberLevelSource = $lastFlashScoreRow['level'] ?? $memberScore['level'] ?? 0; // TODO(2025): verify legacy $row['level'] usage for member row
                        $HTMLOUT .= '
                            <tr>
                                <td>' . number_format($memberRank) . '</td>
                                <td>' . format_username((int) $user['id']) . '</td>
                                <td>' . (int) $memberLevelSource . '</td>
                                <td>' . number_format((float) $memberScore['score']) . '</td>
                            </tr>';
                    }
                }

                $HTMLOUT .= '
                        </tbody>
                    </table>';
            } else {
                $gameIndex = array_search($gamename, $arcadeGames, true);
                $fullgamename = $arcadeGameNames[$gameIndex] ?? '';
                $HTMLOUT .= "
                    <table class='table table-bordered table-striped top20 bottom20'>
                        <thead>
                            <tr>
                                <th colspan='4'>
                                    <div class='size_4 has-text-centered'>
                                        $fullgamename
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class='has-text-centered'>
                                        " . _('Sorry, we cannot save scores of this game or there are no scores saved, yet.') . '
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>';
            }

            $title = _('Old School Arcade');
            $breadcrumbs = [
                "<a href='{$baseUrl}/games.php'>" . _('Games') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
