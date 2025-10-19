<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19 via handler-convert offset=260 batch=5

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class ArcadeHandler
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
            unset($db); // legacy script expected $db bootstrap side-effects

            $allowedPlay = (int) $config->get('allowed.play');
            $classNames = (array) $config->get('class_names');
            $siteName = (string) $config->get('site.name');
            $topScorePoints = (int) $config->get('arcade.top_score_points');
            $gameNames = (array) $config->get('arcade.game_names');
            $games = (array) $config->get('arcade.games');
            $baseurl = (string) $config->get('paths.baseurl');
            $imagesBase = (string) $config->get('paths.images_baseurl');

            $user = check_user_status();

            if (($user['class'] ?? 0) < $allowedPlay) {
                $requiredClassName = $classNames[$allowedPlay] ?? '';
                stderr(_('Error'), _fe('Sorry, you must be a {0} to play in the arcade!', $requiredClassName), 'bottom20');
            } elseif (($user['game_access'] ?? 0) !== 1 || ($user['status'] ?? 0) !== 0) {
                stderr(_('Error'), _('Your gaming rights have been disabled.'), 'bottom20', 'bottom20');
                app_halt('Exit called');
            }

            $html = "
            <div class='has-text-centered'>
                <h1>" . _fe('{0} Old School Arcade!', $siteName) . '</h1>
                <span>' . _fe('Top Scores Earn {0} Karma Points', $topScorePoints) . "</span>
                <div class='level-center top10'>
                    <a class='is-link' href='{$baseurl}/arcade_top_scores.php'>" . _('Top Scores') . '</a>
                </div>
            </div>';

            $body = "
            <div class='level-center'>";

            $list = $gameNames;
            sort($list);
            $index = 0;
            foreach ($list as $gamename) {
                $gameIndex = $index++;
                $gameId = array_search($gamename, $gameNames, true);
                if ($gameId === false) {
                    continue;
                }

                $game = $games[$gameId] ?? '';
                $fullGameName = $gameNames[$gameId] ?? '';
                $body .= "
                <div class='margin10 w-20'>
                    <a href='{$baseurl}/flash.php?gameURI={$game}.swf&amp;gamename={$game}&amp;game_id={$gameIndex}' class='tooltipper' title='" . urlencode($fullGameName) . "'>
                        <img src='{$imagesBase}games/{$game}.png' alt='{$game}' class='round10'>
                    </a>
                </div>";
            }
            $body .= '
            </div>';

            $html .= main_div($body, 'top20');

            $title = _('Arcade');
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
