<?php
declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';
require_once CLASS_DIR . 'class_check.php';

use Pu239\Database;
use Pu239\StatsService;

global $container, $site_config;

/** adgangskontrol */
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

/** @var Database $db */
$db = $container->get(Database::class);
/** @var StatsService $stats */
$stats = $container->get(StatsService::class);

/** pagination (uden function_pager) */
$perpage = max(10, (int)($_GET['pp'] ?? 20));
$page    = max(1,  (int)($_GET['p']  ?? 1));
$offset  = ($page - 1) * $perpage;

/** data */
$users   = $stats->getUserCounts();
$torr    = $stats->getTorrentCounts();
$peers   = $stats->getPeerCounts();
$traf    = $stats->getTraffic24h();
$totalUploaders = $stats->countUploaders();
$rows    = $stats->getUploaders($perpage, $offset);

/** helper */
$bn = function (float|int $n): string {
    return number_format((float)$n, 0, ',', '.');
};

/** hvis projektet stadig har stdhead/stdfoot/wrapper, så brug dem;
    ellers rendér minimal HTML (hardcore-mode uden legacy helpers). */
$have_legacy_layout = function_exists('stdhead') && function_exists('stdfoot');

$title = 'Stats';
$base  = $site_config['paths']['baseurl'] ?? '';
$self  = htmlspecialchars((string)($_SERVER['PHP_SELF'] ?? ''), ENT_QUOTES);

/** simple pager */
$totalPages = max(1, (int)ceil($totalUploaders / $perpage));
$pagerHtml  = '';
if ($totalPages > 1) {
    $pagerHtml .= '<nav class="pager">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $page ? ' style="font-weight:bold"' : '';
        $pagerHtml .= '<a href="' . $self . '?p=' . $i . '&pp=' . $perpage . '"'.$active.'>' . $i . '</a> ';
    }
    $pagerHtml .= '</nav>';
}

/** content (ren HTML, ingen function_html) */
ob_start();
?>
<?php if (!$have_legacy_layout): ?>
<!doctype html>
<html lang="da"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?></title>
<style>
  :root { --gap:12px; --bd:#e5e7eb; --muted:#6b7280; }
  body{font-family:system-ui,Segoe UI,Arial,sans-serif;margin:20px}
  h1{margin:0 0 16px}
  .grid{display:grid;gap:var(--gap);grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
  .card{border:1px solid var(--bd);border-radius:12px;padding:16px}
  .k{color:var(--muted);font-size:.9rem}
  table{width:100%;border-collapse:collapse;margin-top:8px}
  th,td{padding:8px;border-bottom:1px solid var(--bd);text-align:left}
  .pager a{display:inline-block;margin-right:6px;text-decoration:none}
</style>
</head><body>
<?php endif; ?>

<h1>Systemstatus</h1>
<div class="grid">
  <div class="card">
    <div class="k">Brugere</div>
    <div>Total: <?= $bn($users['total'] ?? 0) ?></div>
    <div>Aktiverede: <?= $bn($users['enabled'] ?? 0) ?></div>
  </div>
  <div class="card">
    <div class="k">Torrents</div>
    <div>Total: <?= $bn($torr['total'] ?? 0) ?></div>
  </div>
  <div class="card">
    <div class="k">Peers</div>
    <div>Seeders: <?= $bn($peers['seeders'] ?? 0) ?></div>
    <div>Leechers: <?= $bn($peers['leechers'] ?? 0) ?></div>
  </div>
  <div class="card">
    <div class="k">Seneste 24 timer</div>
    <div>Upload: <?= $bn($traf['up'] ?? 0) ?></div>
    <div>Download: <?= $bn($traf['down'] ?? 0) ?></div>
  </div>
</div>

<div class="card" style="margin-top:16px">
  <div class="k">Uploadere</div>
  <?= $pagerHtml ?>
  <?php if (!$rows): ?>
    <p>Ingen uploadere fundet.</p>
  <?php else: ?>
    <table>
      <thead><tr>
        <th>#</th>
        <th>Bruger</th>
        <th>Antal torrents</th>
        <th>Sidst</th>
      </tr></thead>
      <tbody>
      <?php
      $i = $offset + 1;
      foreach ($rows as $r):
          $name = htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES);
          $uid  = (int)($r['id'] ?? 0);
          $nt   = (int)($r['n_t'] ?? 0);
          $last = $r['last'] ?? null;
          $lastTxt = $last ? date('Y-m-d H:i', is_numeric($last) ? (int)$last : strtotime((string)$last)) : '—';
      ?>
        <tr>
          <td><?= $bn($i++) ?></td>
          <td><a href="<?= $base ?>/userdetails.php?id=<?= $uid ?>"><?= $name ?></a></td>
          <td><?= $bn($nt) ?></td>
          <td><?= $lastTxt ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?= $pagerHtml ?>
  <?php endif; ?>
</div>

<?php if (!$have_legacy_layout): ?>
</body></html>
<?php endif; ?>
<?php
$html = ob_get_clean();

if ($have_legacy_layout) {
    $breadcrumbs = [
        "<a href='{$base}/staffpanel.php'>Staff Panel</a>",
        "<a href='{$self}'>" . htmlspecialchars($title) . '</a>',
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();
} else {
    echo $html;
}
