<?php
declare(strict_types=1);
/**
 * public/rss_pdo_demo.php
 * Minimal demo endpoint showing how to use db()/pdo() to build an RSS feed.
 */
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

header('Content-Type: application/rss+xml; charset=UTF-8');
function xml($s) { return htmlspecialchars((string)$s, ENT_XML1 | ENT_COMPAT, 'UTF-8'); }

$title = 'Pu-239 Demo RSS';
$link  = '/';
$desc  = 'Demo RSS feed powered by PDO';

$items = [];
$err = null;

try {
    $has = db()->fetchValue(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t",
        [':t' => 'torrents']
    );
    if ($has) {
        $rows = db()->fetchAll("SELECT id, name, added FROM torrents ORDER BY added DESC LIMIT 25");
        foreach ($rows as $r) {
            $items[] = [
                'title' => $r['name'] ?? ('#' . ($r['id'] ?? '')),
                'link'  => '/details.php?id=' . ($r['id'] ?? ''),
                'guid'  => 'torrent:' . ($r['id'] ?? ''),
                'pubDate' => isset($r['added']) ? gmdate('r', strtotime((string)$r['added'])) : gmdate('r'),
                'description' => '',
            ];
        }
    }
} catch (Throwable $e) {
    $err = $e->getMessage();
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<rss version="2.0">
  <channel>
    <title><?= xml($title) ?></title>
    <link><?= xml($link) ?></link>
    <description><?= xml($desc) ?></description>
    <language>en-us</language>
    <ttl>15</ttl>
<?php if (!empty($items)): ?>
<?php foreach ($items as $it): ?>
    <item>
      <title><?= xml($it['title']) ?></title>
      <link><?= xml($it['link']) ?></link>
      <guid><?= xml($it['guid']) ?></guid>
      <pubDate><?= xml($it['pubDate']) ?></pubDate>
      <description><?= xml($it['description']) ?></description>
    </item>
<?php endforeach; ?>
<?php else: ?>
    <item>
      <title><?= xml($title) ?> (empty demo)</title>
      <link><?= xml($link) ?></link>
      <guid>demo:empty</guid>
      <pubDate><?= gmdate('r') ?></pubDate>
      <description><?php if ($err) { echo xml('DB error: ' . $err); } else { echo xml('No data or torrents table missing.'); } ?></description>
    </item>
<?php endif; ?>
  </channel>
</rss>
