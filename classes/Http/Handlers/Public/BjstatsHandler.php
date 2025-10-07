<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=70-5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class BjstatsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=70-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $allowedPlay = (int) $config->get('allowed.play');
            $classNames = (array) $config->get('class_names');

            $user = check_user_status();

            if (!has_access($user['class'], $allowedPlay, '')) {
                $requiredClassName = $classNames[$allowedPlay] ?? '';
                stderr(_('Error'), _fe('Sorry, you must be a {0} to play blackjack!', $requiredClassName), 'bottom20');
            } elseif (($user['game_access'] ?? 0) !== 1 || ($user['status'] ?? 0) !== 0) {
                stderr(_('Error'), _('Your gaming rights have been disabled.'), 'bottom20');
            }

            $minGames = 10;

            $mostGames = $db->fetchAll(
                'SELECT id,
                        username,
                        bjwins AS wins,
                        bjlosses AS losses,
                        (bjwins + bjlosses) AS games
                 FROM users
                 WHERE (bjwins + bjlosses) > :minGames
                 ORDER BY games DESC, wins DESC
                 LIMIT 10',
                ['minGames' => $minGames],
            );
            $htmlOut = $this->renderTable($mostGames, _('Most Games Played'));

            $highestWinPercent = $db->fetchAll(
                'SELECT id,
                        username,
                        bjwins AS wins,
                        bjlosses AS losses,
                        (bjwins + bjlosses) AS games,
                        (bjwins / NULLIF(bjwins + bjlosses, 0)) AS winperc
                 FROM users
                 WHERE (bjwins + bjlosses) > :minGames
                 ORDER BY winperc DESC, wins DESC
                 LIMIT 10',
                ['minGames' => $minGames],
            );
            $htmlOut .= $this->renderTable($highestWinPercent, _('Highest Win Percentage'));

            $mostCreditWon = $db->fetchAll(
                'SELECT id,
                        username,
                        bjwins AS wins,
                        bjlosses AS losses,
                        (bjwins - bjlosses) AS winnings
                 FROM users
                 WHERE (bjwins + bjlosses) > :minGames
                 ORDER BY winnings DESC, wins DESC
                 LIMIT 10',
                ['minGames' => $minGames],
            );
            $htmlOut .= $this->renderTable($mostCreditWon, _('Most Credit Won'));

            $mostCreditLost = $db->fetchAll(
                'SELECT id,
                        username,
                        bjwins AS wins,
                        bjlosses AS losses,
                        (bjlosses - bjwins) AS losings,
                        (bjwins / NULLIF(bjwins + bjlosses, 0)) AS winperc
                 FROM users
                 WHERE (bjwins + bjlosses) > :minGames
                 ORDER BY losings DESC, losses DESC
                 LIMIT 10',
                ['minGames' => $minGames],
            );
            $htmlOut .= $this->renderTable($mostCreditLost, _('Most Credit Lost'));

            $title = _('Blackjack Stats');
            $breadcrumbs = [
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
     * @param array<int,array<string,mixed>> $rows
     */
    private function renderTable(array $rows, string $caption): string
    {
        $html = "<h1 class='has-text-centered'>$caption</h1>";
        $heading = '
                <tr>
                    <th>' . _('Rank') . '</th>
                    <th>' . _('User') . "</th>
                    <th class='colhead has-text-right'>" . _('Wins') . "</th>
                    <th class='colhead has-text-right'>" . _('Losses') . "</th>
                    <th class='colhead has-text-right'>" . _('Games') . "</th>
                    <th class='colhead has-text-right'>" . _('Percentage') . "</th>
                    <th class='colhead has-text-right'>" . _('Win/Loss') . '</th>
                </tr>';

        $rank = 0;
        $body = '';
        foreach ($rows as $row) {
            $wins = (int) ($row['wins'] ?? 0);
            $losses = (int) ($row['losses'] ?? 0);
            $games = (int) ($row['games'] ?? ($wins + $losses));
            $winPercentage = $games > 0 ? number_format(($wins / $games) * 100, 1) : '0.0';
            $diff = $wins - $losses;
            if ($diff >= 0) {
                $winLoss = mksize($diff * 100 * 1024 * 1024);
            } else {
                $winLoss = '-' . mksize(abs($diff) * 100 * 1024 * 1024);
            }

            ++$rank;
            $body .= sprintf(
                "\n                <tr>\n                    <td>%d</td>\n                    <td>%s</td>\n                    <td class='has-text-right'>%s</td>\n                    <td class='has-text-right'>%s</td>\n                    <td class='has-text-right'>%s</td>\n                    <td class='has-text-right'>%s</td>\n                    <td class='has-text-right'>%s</td>\n                </tr>",
                $rank,
                format_username((int) ($row['id'] ?? 0)),
                number_format($wins, 0),
                number_format($losses, 0),
                number_format($games, 0),
                $winPercentage,
                $winLoss,
            );
        }

        if ($body === '') {
            $body .= "
                <tr>
                    <td colspan='7' class='has-text-centered'>" . _('No Game Stats') . '</td>
                </tr>';
        }

        $html .= main_table($body, $heading);

        return $html;
    }
}
