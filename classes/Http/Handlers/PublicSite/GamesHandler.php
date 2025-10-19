<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T21:32:58Z via handler-convert offset=250 batch=5

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;
use Pu239\Database;

use function array_fill;
use function dirname;

final class GamesHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T21:32:58Z via handler-convert offset=250 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            $fluent = $db;

            $allowedPlay = (int) $config->get('allowed.play');
            $classNames = (array) $config->get('class_names');
            $siteName = (string) $config->get('site.name');
            $baseUrl = (string) $config->get('paths.baseurl');
            $imagesBaseUrl = (string) $config->get('paths.images_baseurl');

            $user = check_user_status();

            $HTMLOUT = '';

            if (($user['class'] ?? 0) < $allowedPlay) {
                $requiredClassName = $classNames[$allowedPlay] ?? '';
                stderr(_('Error'), _fe('Sorry, you must be a {0} to play these games!', $requiredClassName), 'bottom20');
            } elseif (($user['game_access'] ?? 0) !== 1 || ($user['status'] ?? 0) !== 0) {
                stderr(_('Error'), _('Your gaming rights have been disabled.'), 'bottom20');
            }

            $colorMap = array_fill(1, 9, 'has-text-danger');

            $rows = $db->fetchAll(
                'SELECT game_id FROM blackjack WHERE status = :status ORDER BY game_id',
                [
                    ':status' => 'waiting',
                ],
            );
            foreach ($rows as $count) {
                $gameId = (int) ($count['game_id'] ?? 0);
                if ($gameId >= 1 && $gameId <= 9) {
                    $colorMap[$gameId] = 'has-text-success';
                }
            }

            $casinoCount = $fluent->from('casino')
                ->select(null)
                ->select('COUNT(userid) AS count')
                ->where('deposit > 0')
                ->where('userid != ?', $user['id'])
                ->fetch('count');
            if ((int) $casinoCount > 0) {
                $colorMap[9] = 'green';
            }

            $color1 = $colorMap[1];
            $color2 = $colorMap[2];
            $color3 = $colorMap[3];
            $color4 = $colorMap[4];
            $color5 = $colorMap[5];
            $color6 = $colorMap[6];
            $color7 = $colorMap[7];
            $color8 = $colorMap[8];
            $color9 = $colorMap[9];

            $HTMLOUT = "
            <div class='has-text-centered bottom20'>
                <h1>{$siteName} Games!</h1>
                <h3>" . _fe('Welcome To The {0} Casino, Please Select A Game Below To Play.', $siteName) . '</h3>
            </div>' . main_div("
            <div class='columns is-multiline is-variable is-0-mobile is-1-tablet is-2-desktop'>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=1'><div class='has-text-centered {$color1}'>" . _fe('Blackjack {0}', '1GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=10'><div class='has-text-centered {$color2}'>" . _fe('Blackjack {0}', '10GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=20'><div class='has-text-centered {$color3}'>" . _fe('Blackjack {0}', '20GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=50'><div class='has-text-centered {$color4}'>" . _fe('Blackjack {0}', '50GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/casino.php'><div class='has-text-centered {$color9}'>" . _('Casino') . "</div>
                        <img src='{$imagesBaseUrl}casino.jpg' alt='casino' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=100'><div class='has-text-centered {$color5}'>" . _fe('Blackjack {0}', '100GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=250'><div class='has-text-centered {$color6}'>" . _fe('Blackjack {0}', '250GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=500'><div class='has-text-centered {$color7}'>" . _fe('Blackjack {0}', '500GB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
                <div class='column is-one-third'>
                    <a href='{$baseUrl}/blackjack.php?id=1000'><div class='has-text-centered {$color8}'>" . _fe('Blackjack {0}', '1TB') . "</div>
                        <img src='{$imagesBaseUrl}blackjack.jpg' alt='blackjack' class='round10 w-100'>
                    </a>
                </div>
            </div>", null, 'padding20');

            $title = _('Games');
            $breadcrumbs = [
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
