<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Database;
global $container;
$db = $container->get(Database::class);

require_once __DIR__ . '/../include/bittorrent.php';
check_user_status();

$games = $db->fetchAll(
    'SELECT gamenum, IFNULL(UNIX_TIMESTAMP(finished), 0) AS ended, IFNULL(UNIX_TIMESTAMP(started), 0) AS started
        FROM triviasettings
        GROUP BY gamenum, finished, started
        ORDER BY gamenum DESC
        LIMIT :limit',
    [':limit' => 10]
);
$table = "
            <div class='portlet'>";
$div = '';
foreach ($games as $result) {
    $gamenum = (int) $result['gamenum'];
    $ended = $result['ended'] >= 1 ? get_date((int) $result['ended'], 'LONG') : 0;
    $started = $result['started'] >= 1 ? get_date((int) $result['started'], 'LONG') : 0;
    $players = $db->fetchAll(
        'SELECT t.gamenum, t.user_id, COUNT(t.correct) AS correct,
                (SELECT COUNT(correct) FROM triviausers WHERE correct = 0 AND user_id = t.user_id AND gamenum = :gamenum) AS incorrect,
                u.username, u.modcomment
            FROM triviausers AS t
            INNER JOIN users AS u ON u.id=t.user_id
            INNER JOIN triviasettings AS s ON s.gamenum = t.gamenum
            WHERE t.correct = 1 AND t.gamenum = :gamenum
            GROUP BY t.user_id
            ORDER BY correct DESC, incorrect
            LIMIT :limit',
        [
            ':gamenum' => $gamenum,
            ':limit' => 10,
        ]
    );
    if (!empty($players)) {
        $i = 0;
        $date = $result['ended'] >= 1 ? "Ended: $ended" : "Started: $started";
        $div .= "
                <div class='bg-02 has-text-centered top20 round5'>
                    <div class='padtop20'>
                        <h1>Game #{$gamenum} $date</h1>
                    </div>
                    <table class='table table-bordered table-striped'>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Ratio</th>
                                <th>Correct</th>
                                <th>Incorrect</th>
                            </tr>
                        </thead>
                        <tbody>";

        foreach ($players as $player) {
            $correct = (int) $player['correct'];
            $incorrect = (int) $player['incorrect'];
            $div .= '
                        <tr>
                            <td>' . format_username((int) $player['user_id']) . '</td>
                            <td>' . sprintf('%.2f%%', $correct / ($correct + $incorrect) * 100) . '</td>
                            <td>' . $correct . '</td>
                            <td>' . $incorrect . '</td>
                        </tr>';
        }
        $div .= '
                        </tbody>
                    </table>
                </div>';
    }
}
if (empty($div)) {
    $div = main_div('No Trivia Results', 'has-text-centered', 'padding20');
}
$table .= $div . "
            </div>";
$title = _('Trivia');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($table) . stdfoot();
