<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:47:17Z via handler-convert offset=185 size=5

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;
use Pu239\Database;
use RuntimeException;

final class BjstatsHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:47:17Z via handler-convert offset=185 size=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

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
            $stats = [];
            $stats[] = [
                'rows' => $db->fetchAll(
                    'SELECT id, username, bjwins AS wins, bjlosses AS losses, bjwins + bjlosses AS games '
                    . 'FROM users '
                    . 'WHERE bjwins + bjlosses > :mingames '
                    . 'ORDER BY games DESC '
                    . 'LIMIT 10',
                    ['mingames' => $minGames]
                ),
                'title' => _('Most Games Played'),
            ];
            $stats[] = [
                'rows' => $db->fetchAll(
                    'SELECT id, username, bjwins AS wins, bjlosses AS losses, bjwins + bjlosses AS games, '
                    . 'bjwins / (bjwins + bjlosses) AS winperc '
                    . 'FROM users '
                    . 'WHERE bjwins + bjlosses > :mingames '
                    . 'ORDER BY winperc DESC '
                    . 'LIMIT 10',
                    ['mingames' => $minGames]
                ),
                'title' => _('Highest Win Percentage'),
            ];
            $stats[] = [
                'rows' => $db->fetchAll(
                    'SELECT id, username, bjwins AS wins, bjlosses AS losses, bjwins + bjlosses AS games, '
                    . 'bjwins - bjlosses AS winnings '
                    . 'FROM users '
                    . 'WHERE bjwins + bjlosses > :mingames '
                    . 'ORDER BY winnings DESC '
                    . 'LIMIT 10',
                    ['mingames' => $minGames]
                ),
                'title' => _('Most Credit Won'),
            ];
            $stats[] = [
                'rows' => $db->fetchAll(
                    'SELECT id, username, bjwins AS wins, bjlosses AS losses, bjwins + bjlosses AS games, '
                    . 'bjlosses - bjwins AS losings, bjwins / (bjwins + bjlosses) AS winperc '
                    . 'FROM users '
                    . 'WHERE bjwins + bjlosses > :mingames '
                    . 'ORDER BY losings DESC '
                    . 'LIMIT 10',
                    ['mingames' => $minGames]
                ),
                'title' => _('Most Credit Lost'),
            ];

            $htmlOut = '';
            foreach ($stats as $stat) {
                $htmlOut .= $this->renderTable($stat['rows'], (string) $stat['title']);
            }

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
     * @param array<int, array<string, mixed>> $rows
     */
    private function renderTable(array $rows, string $caption): string
    {
        $html = "<h1 class='has-text-centered'>{$caption}</h1>";
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
            ++$rank;
            $games = (int) ($row['games'] ?? 0);
            $wins = (int) ($row['wins'] ?? 0);
            $losses = (int) ($row['losses'] ?? 0);
            $winPerc = $games > 0 ? number_format(($wins / $games) * 100, 1) : '0.0';
            $diff = $wins - $losses;
            if ($diff >= 0) {
                $plusMinus = mksize($diff * 100 * 1024 * 1024);
            } else {
                $plusMinus = '-' . mksize(($losses - $wins) * 100 * 1024 * 1024);
            }
            $body .= sprintf(
                "\n            <tr>\n                <td>%d</td>\n                <td>%s</td>\n                <td class='has-text-right'>%s</td>\n                <td class='has-text-right'>%s</td>\n                <td class='has-text-right'>%s</td>\n                <td class='has-text-right'>%s</td>\n                <td class='has-text-right'>%s</td>\n            </tr>",
                $rank,
                format_username((int) ($row['id'] ?? 0)),
                number_format($wins, 0),
                number_format($losses, 0),
                number_format($games, 0),
                $winPerc,
                $plusMinus
            );
        }

        if ($body === '') {
            $body = "\n            <tr>\n                <td colspan='7' class='has-text-centered'>" . _('No Game Stats') . '</td>\n            </tr>';
        }

        $html .= main_table($body, $heading);

        return $html;
    }
}
