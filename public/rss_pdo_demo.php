<?php
declare(strict_types=1);
/**
 * public/rss_pdo_demo.php
 * PDO-demo uden short_open_tag false positives.
 */
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

header('Content-Type: application/rss+xml; charset=UTF-8');

$xml = [];
$xml[] = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>'; // undgår literal "<?xml"

$title = 'Pu-239 Demo RSS';
$link  = '/';
$desc  = 'Demo RSS feed powered by PDO';

$items = [];
$err = null;

/** HTML/XML escape helper */
$xmlEsc = static function ($s) {
    return htmlspecialchars((string)$s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
};

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

$xml[] = '<rss version="2.0">';
$xml[] = '  <channel>';
$xml[] = '    <title>' . $xmlEsc($title) . '</title>';
$xml[] = '    <link>' . $xmlEsc($link) . '</link>';
$xml[] = '    <description>' . $xmlEsc($desc) . '</description>';
$xml[] = '    <language>en-us</language>';
$xml[] = '    <ttl>15</ttl>';

if (!empty($items)) {
    foreach ($items as $it) {
        $xml[] = '    <item>';
        $xml[] = '      <title>' . $xmlEsc($it['title']) . '</title>';
        $xml[] = '      <link>' . $xmlEsc($it['link']) . '</link>';
        $xml[] = '      <guid>' . $xmlEsc($it['guid']) . '</guid>';
        $xml[] = '      <pubDate>' . $xmlEsc($it['pubDate']) . '</pubDate>';
        $xml[] = '      <description>' . $xmlEsc($it['description']) . '</description>';
        $xml[] = '    </item>';
    }
} else {
    $xml[] = '    <item>';
    $xml[] = '      <title>' . $xmlEsc($title) . ' (empty demo)</title>';
    $xml[] = '      <link>' . $xmlEsc($link) . '</link>';
    $xml[] = '      <guid>demo:empty</guid>';
    $xml[] = '      <pubDate>' . gmdate('r') . '</pubDate>';
    $xml[] = '      <description>' . $xmlEsc($err ? ('DB error: ' . $err) : 'No data or torrents table missing.') . '</description>';
    $xml[] = '    </item>';
}

$xml[] = '  </channel>';
$xml[] = '</rss>';

echo implode("\n", $xml);
