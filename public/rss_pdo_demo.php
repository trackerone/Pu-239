<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);
header('Content-Type: application/rss+xml; charset=UTF-8');

// Konstruer XML-deklaration uden short open tag
echo '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\n";

$channelTitle = 'Pu-239 RSS Demo';
$channelLink  = 'https://example.com/';
$channelDesc  = 'Demo feed without short open tags';

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

echo "<rss version=\"2.0\">\n";
echo "  <channel>\n";
echo '    <title>' . e($channelTitle) . "</title>\n";
echo '    <link>' . e($channelLink) . "</link>\n";
echo '    <description>' . e($channelDesc) . "</description>\n";
echo "    <item>\n";
echo "      <title>Demo item</title>\n";
echo "      <link>https://example.com/demo</link>\n";
echo "      <guid isPermaLink=\"false\">demo-1</guid>\n";
echo '      <pubDate>' . gmdate('D, d M Y H:i:s') . " GMT</pubDate>\n";
echo "      <description>Minimal RSS item for Static Guard compliance.</description>\n";
echo "    </item>\n";
echo "  </channel>\n";
echo "</rss>\n";
