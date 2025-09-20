<?php
declare(strict_types=1);

use PU239\Config\ConfigRepository;
use Pu239\Achievementlist;
use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$images_baseurl = '';
global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);

require_once __DIR__ . '/../include/bittorrent.php';

$HTMLOUT = '';
$user = check_user_status();
$achievementlist = $container->get(Achievementlist::class);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['class'] >= UC_MAX) {
    // TODO(2025): csrf on POST where missing
    $values = [
        'achievename' => htmlsafechars(trim($_POST['achievename'] ?? '')),
        'notes' => htmlsafechars(trim($_POST['notes'] ?? '')),
        'clienticon' => htmlsafechars(trim($_POST['clienticon'] ?? '')),
    ];
    $achievementlist->add($values);
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
$HTMLOUT .= '<h1>' . _('Achievements List') . '</h1>';

if ($rows === []) {
    $HTMLOUT .= main_div(
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
    $images_baseurl = (string) $config->get('paths.images_baseurl');
    foreach ($rows as $arr) {
        $notes = htmlsafechars($arr['notes']);
        $count = (int) $arr['count'];
        $clienticon = '';
        if ($arr['clienticon'] !== '') {
            $clienticon = "<img src='" . $images_baseurl . "achievements/" .
                htmlsafechars($arr['clienticon']) .
                "' class='tooltipper' title='" . htmlsafechars($arr['achievename']) .
                "' alt='" . htmlsafechars($arr['achievename']) . "'>";
        }
        $body .= "
            <tr>
                <td>{$clienticon}</td>
                <td>{$notes}</td>
                <td>" . _pfe('{0} time', '{0} times', $count) . '</td>
            </tr>';
    }
    $HTMLOUT .= main_table($body, $heading);
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

    $HTMLOUT .= "
    <h2>" . _('Add an achievement to list.') . "</h2>
    <form method='post' action='achievementlist.php' enctype='multipart/form-data' accept-charset='utf-8'>" .
        main_table($formRows) . '
    </form>';
}

$title = _('Achievements List');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];

echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT, 'has-text-centered') . stdfoot();
