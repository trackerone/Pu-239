<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Database;
use Pu239\Session;

global $container, $site_config;
/** @var Database $db */
$db = $container->get(Database::class);
/** @var Session $session */
$session = $container->get(Session::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

function h(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function get_int(string $key, int $default = 0): int
{
    return isset($_REQUEST[$key]) ? (int) $_REQUEST[$key] : $default;
}

function get_str(string $key, string $default = ''): string
{
    return isset($_REQUEST[$key]) ? (string) $_REQUEST[$key] : $default;
}

$curuser = $session->get('user') ?? ['id' => 0];

if (isset($_POST['deal_with_report'])) {
    $reportId = get_int('report_id');
    $body = trim(get_str('body'));

    if ($reportId > 0) {
        $db->run(
            'UPDATE reports
                SET delt_with = 1,
                    how_delt_with = :body,
                    when_delt_with = :ts,
                    delt_with_by = :uid
              WHERE id = :id',
            [
                'body' => $body,
                'ts' => [TIME_NOW, PDO::PARAM_INT],
                'uid' => [(int) ($curuser['id'] ?? 0), PDO::PARAM_INT],
                'id' => [$reportId, PDO::PARAM_INT],
            ]
        );
        $session->set('is-success', _('Report marked as resolved.'));
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$show_all = isset($_GET['show']) && $_GET['show'] === 'all';
$page = max(1, get_int('page', 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;
$where = $show_all ? '1=1' : 'r.delt_with = 0';

$total = (int) $db->fetchValue(
    "SELECT COUNT(*) FROM reports AS r WHERE $where"
);

$sql = <<<SQL
    SELECT
        r.id,
        r.added,
        r.reported_by,
        r.reporting_what,
        r.reporting_id,
        r.reason,
        r.delt_with,
        r.how_delt_with,
        r.when_delt_with,
        r.delt_with_by,
        u1.username AS reporter_name,
        u2.username AS dealt_by_name
    FROM reports AS r
    LEFT JOIN users AS u1 ON (u1.id = r.reported_by)
    LEFT JOIN users AS u2 ON (u2.id = r.delt_with_by)
    WHERE $where
    ORDER BY r.id DESC
    LIMIT :limit OFFSET :offset
SQL;
$rows = $db->run($sql, [
    'limit' => [$per_page, PDO::PARAM_INT],
    'offset' => [$offset, PDO::PARAM_INT],
])->fetchAll();

$base = h($_SERVER['PHP_SELF']);
$toggle_url = $show_all ? $base : $base . '?show=all';
$toggle_label = $show_all ? _('Show open reports only') : _('Show all reports');
$toggle_button = "<a class='button is-small' href='{$toggle_url}'>$toggle_label</a>";

$pages = (int) ceil($total / $per_page);
$pagination = '';
if ($pages > 1) {
    $links = [];
    for ($i = 1; $i <= $pages; ++$i) {
        $query = $show_all ? 'show=all&amp;' : '';
        $href = sprintf('%s?%spage=%d', $base, $query, $i);
        $class = $i === $page ? 'button is-small is-link is-light' : 'button is-small';
        $links[] = "<a class='{$class}' href='{$href}'>$i</a>";
    }
    $pagination = "<div class='level is-mobile'><div class='level-item buttons'>{$toggle_button}" . implode('', $links) . '</div></div>';
} else {
    $pagination = "<div class='level is-mobile'><div class='level-item'>{$toggle_button}</div></div>";
}

$HTMLOUT = $pagination;

if ($rows === []) {
    $HTMLOUT .= "<div class='card has-text-centered padding20'>" . _('No reports found.') . '</div>';
} else {
    $header = '
        <tr>
            <th>' . _('ID') . '</th>
            <th>' . _('Reported item') . '</th>
            <th>' . _('Reporter') . '</th>
            <th>' . _('Reason') . '</th>
            <th>' . _('Status') . '</th>
            <th>' . _('Actions') . '</th>
        </tr>';

    $body = '';
    foreach ($rows as $row) {
        $reportId = (int) $row['id'];
        $reportLink = h((string) $row['reporting_what']);
        if ((int) $row['reporting_id'] > 0) {
            $reportLink .= ' #' . (int) $row['reporting_id'];
        }

        $reporter = _('System');
        if (!empty($row['reported_by'])) {
            $name = $row['reporter_name'] ?? ('#' . $row['reported_by']);
            $reporter = "<a class='is-link' href='{$site_config['paths']['baseurl']}/userdetails.php?id=" . (int) $row['reported_by'] . "'>" . h($name) . '</a>';
        }

        $reason = $row['reason'] !== '' ? format_comment((string) $row['reason']) : _('No reason provided.');

        if ((int) $row['delt_with'] === 1) {
            $status = _('Resolved');
            if (!empty($row['when_delt_with'])) {
                $status .= '<br>' . get_date((int) $row['when_delt_with'], 'LONG', 1);
            }
            if (!empty($row['dealt_by_name'])) {
                $status .= '<br>' . sprintf('%s: %s', _('By'), h($row['dealt_by_name']));
            }
            if ($row['how_delt_with'] !== '') {
                $status .= '<br>' . h($row['how_delt_with']);
            }
            $actionCell = '<span class="has-text-grey-light">' . _('Handled') . '</span>';
        } else {
            $status = _('Open');
            $actionCell = "<form method='post' action='{$base}' class='is-inline'>" .
                "<input type='hidden' name='report_id' value='{$reportId}'>" .
                "<input type='hidden' name='deal_with_report' value='1'>" .
                "<div class='field has-addons'>" .
                "<div class='control'><input class='input is-small' type='text' name='body' placeholder='" . _('Resolution notes') . "'></div>" .
                "<div class='control'><button class='button is-small is-success' type='submit'>" . _('Resolve') . '</button></div>' .
                '</div>' .
                '</form>';
        }

        $body .= "
            <tr>
                <td>{$reportId}</td>
                <td>{$reportLink}</td>
                <td>{$reporter}</td>
                <td>{$reason}</td>
                <td>{$status}</td>
                <td>{$actionCell}</td>
            </tr>";
    }

    $HTMLOUT .= main_table($body, $header);
    $HTMLOUT .= $pagination;
}

$title = _('Reports');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];

echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
