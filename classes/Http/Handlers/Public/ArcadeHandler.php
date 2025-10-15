<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=140 batch=5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class ArcadeHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=140 batch=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $allowedPlayClass = (int) $config->get('allowed.play');
            $classNames = (array) $config->get('class_names');
            $siteName = (string) $config->get('site.name');
            $topScorePoints = (int) $config->get('arcade.top_score_points');
            $gameNames = (array) $config->get('arcade.game_names');
            $games = (array) $config->get('arcade.games');
            $baseUrl = (string) $config->get('paths.baseurl');
            $imagesBaseUrl = (string) $config->get('paths.images_baseurl');

            $user = check_user_status();

            if (($user['class'] ?? 0) < $allowedPlayClass) {
                $requiredClassName = $classNames[$allowedPlayClass] ?? '';
                stderr(_('Error'), _fe('Sorry, you must be a {0} to play in the arcade!', $requiredClassName), 'bottom20');
            } elseif (($user['game_access'] ?? 0) !== 1 || ($user['status'] ?? 0) !== 0) {
                stderr(_('Error'), _('Your gaming rights have been disabled.'), 'bottom20', 'bottom20');
                app_halt('Exit called');
            }

            $htmlOut = "
                <div class='has-text-centered'>
                    <h1>" . _fe('{0} Old School Arcade!', $siteName) . '</h1>
                    <span>' . _fe('Top Scores Earn {0} Karma Points', $topScorePoints) . "</span>
                    <div class='level-center top10'>
                        <a class='is-link' href='{$baseUrl}/arcade_top_scores.php'>" . _('Top Scores') . '</a>
                    </div>
                </div>';

            $body = "
                <div class='level-center'>";

            $list = $gameNames;
            sort($list);

            $index = 0;
            foreach ($list as $gameName) {
                $gameIndex = array_search($gameName, $gameNames, true);
                if ($gameIndex === false) {
                    continue;
                }

                $gameKey = (string) ($games[$gameIndex] ?? '');
                $fullGameName = (string) ($gameNames[$gameIndex] ?? '');
                if ($gameKey === '') {
                    continue;
                }

                $body .= "
                    <div class='margin10 w-20'>
                        <a href='{$baseUrl}/flash.php?gameURI={$gameKey}.swf&amp;gamename={$gameKey}&amp;game_id={$index}' class='tooltipper' title='" . urlencode($fullGameName) . "'>
                            <img src='{$imagesBaseUrl}games/{$gameKey}.png' alt='{$gameKey}' class='round10'>
                        </a>
                    </div>";

                ++$index;
            }

            $body .= '
                </div>';
            $htmlOut .= main_div($body, 'top20');

            $title = _('Arcade');
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
}
