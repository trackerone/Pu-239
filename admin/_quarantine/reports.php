<?php
declare(strict_types = 1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';
require_once CLASS_DIR . 'class_check.php';

use Pu239\Database;
use Pu239\Session;

global $container, $site_config;
/** @var Database $db */
$db = $container->get(Database::class);
/** @var Session $session */
$session = $container->get(Session::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

// ----------------------------------------------------------------------------
// Helpers
// ----------------------------------------------------------------------------
function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function get_int(string $key, int $default = 0): int
{
    return isset($_REQUEST[$key]) ? (int) $_REQUEST[$key] : $default;
}

function get_str(string $key, string $default = ''): string
{
    return isset($_REQUEST[$key]) ? (string) $_REQUEST[$key] : $default;
}

function now_ts(): int
{
    return (int) (defined('TIME_NOW') ? TIME_NOW : time());
}

// current user
$curuser = $session->get('user') ?? ['id' => 0, 'username' => 'system'];

// ----------------------------------------------------------------------------
// Deal-with handler (mark report as handled)
// ----------------------------------------------------------------------------
if (isset($_POST['deal_with_report'])) {
    $report_id = get_int('report_id', 0);
    $body = trim(get_str('body', ''));

    if ($report_id > 0) {
        $sql = "
            UPDATE reports
               SET delt_with = 1,
                   how_delt_with = :how,
                   when_delt_with = :when_ts,
                   delt_with_by = :who
             WHERE id = :id
        ";
        $db->perform($sql, [
            'how'     => $body,
            'when_ts' => now_ts(),
            'who'     => (int) ($curuser['id'] ?? 0),
            'id'      => $report_id,
        ]);
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------------------------------------------------------------
// Filters & pagination
// ----------------------------------------------------------------------------
$show_all   = isset($_GET['show']) && $_GET['show'] === 'all';
$page       = max(1, get_int('page', 1));
$per_page   = 50;
$offset     = ($page - 1) * $per_page;

// ----------------------------------------------------------------------------
// Count & fetch
// ----------------------------------------------------------------------------
$where = $show_all ? '1=1' : 'r.delt_with = 0';

$total = (int) $db->selectValue("
    SELECT COUNT(*)
      FROM reports AS r
     WHERE $where
");

$sql = "
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
        u1.username  AS reporter_name,
        u2.username  AS dealt_by_name
    FROM reports AS r
    LEFT JOIN users AS u1 ON (u1.id = r.reported_by)
    LEFT JOIN users AS u2 ON (u2.id = r.delt_with_by)
    WHERE $where
    ORDER BY r.id DESC
    LIMIT :limit OFFSET :offset
";
$rows = $db->run($sql, [
    'limit'  => $per_page,
    'offset' => $offset,
])->fetchAll();

// ----------------------------------------------------------------------------
// Render
// ----------------------------------------------------------------------------
$base = h($_SERVER['PHP_SELF']);
$toggle_url = $show_all ? $base
