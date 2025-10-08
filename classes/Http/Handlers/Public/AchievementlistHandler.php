<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=95-5

namespace PU239\Http\Handlers\Public;

use Pu239\Achievementlist;
use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class AchievementlistHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=95-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $escape = $meta['escape'] ?? static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $user = check_user_status();
            /** @var Achievementlist $achievementList */
            $achievementList = $container->get(Achievementlist::class);
            $message = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['class'] >= UC_MAX) {
                // TODO(2025): csrf
                $values = [
                    'achievename' => $escape(trim($_POST['achievename'] ?? '')),
                    'notes' => $escape(trim($_POST['notes'] ?? '')),
                    'clienticon' => $escape(trim($_POST['clienticon'] ?? '')),
                ];
                $achievementList->add($values);
                $message = _fe('A New achievment has been added. Achievement: [{0}]', $values['achievename']);
            }

            $sql = <<<SQL
    SELECT
        a1.id,
        a1.achievename,
        a1.notes,
        a1.clienticon,
        (
            SELECT COUNT(a2.id)
            FROM achievements AS a2
            WHERE a2.achievement = a1.achievename
        ) AS count
    FROM achievementlist AS a1
    ORDER BY a1.id
SQL;
            $rows = $db->toArray($sql);
            $html = '<h1>' . _('Achievements List') . '</h1>';

            if ($rows === []) {
                $html .= main_div(
                    "<div class='has-text-centered padding20'>" .
                    _('There are currently no achievements added to the list!<br>The staff has been slacking') .
                    '!</div>',
                    'bottom20'
                );
            } else {
                $heading = '
            <tr>
                <th>' . _('Achievement Name') . '</th>
                <th>' . _('Description') . '</th>
                <th>' . _('Earned') . '</th>
            </tr>';
                $body = '';
                $imagesBaseUrl = (string) $config->get('paths.images_baseurl');
                $imagesBaseUrlEscaped = $escape($imagesBaseUrl);
                foreach ($rows as $row) {
                    $notes = $escape($row['notes']);
                    $count = (int) $row['count'];
                    $clientIcon = '';
                    if ($row['clienticon'] !== '') {
                        $iconPath = $imagesBaseUrlEscaped . 'achievements/' . $escape($row['clienticon']);
                        $title = $escape($row['achievename']);
                        $clientIcon = "<img src='{$iconPath}' class='tooltipper' title='{$title}' alt='{$title}'>";
                    }
                    $body .= "
            <tr>
                <td>{$clientIcon}</td>
                <td>{$notes}</td>
                <td>" . _pfe('{0} time', '{0} times', $count) . '</td>
            </tr>';
                }
                $html .= main_table($body, $heading);
            }

            if ($user['class'] >= UC_MAX) {
                $formRows = "
            <tr>
                <td class='w-15'>" . _('Achievement Name') . "</td>
                <td><input class='w-100' type='text' name='achievename'></td>
            </tr>
            <tr>
                <td>" . _('Achievement Icon') . "</td>
                <td><textarea class='w-100' rows='3' name='clienticon'></textarea></td>
            </tr>
            <tr>
                <td>" . _('Description') . "</td>
                <td><textarea class='w-100' rows='6' name='notes'></textarea></td>
            </tr>
            <tr>
                <td colspan='2' class='has-text-centered'>
                    <input type='submit' name='okay' value='" . _('Add Me') . "!' class='button is-small'>
                </td>
            </tr>";

                $html .= "
    <h2>" . _('Add an achievement to list.') . "</h2>
    <form method='post' action='achievementlist.php' enctype='multipart/form-data' accept-charset='utf-8'>" .
                    main_table($formRows) . '
    </form>';
            }

            if ($message !== '') {
                $html = main_div("<div class='has-text-centered padding10'>" . $message . '</div>', 'bottom10') . $html;
            }

            $title = _('Achievements List');
            $self = $escape($_SERVER['PHP_SELF'] ?? '');
            $breadcrumbs = [
                "<a href='{$self}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html, 'has-text-centered') . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
