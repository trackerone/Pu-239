<?php
declare(strict_types=1);

header('Content-Type: application/rss+xml; charset=UTF-8');

// Konstruer XML-deklaration uden short open tag
echo '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\n";

$channelTitle = 'Pu-239 RSS Demo';
$channelLink  = 'https://example.com/';
$channelDesc  = 'Demo feed without short open tags';

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<rss version="2.0">
  <channel>
    <title><?php echo e($channelTitle); ?></title>
    <link><?php echo e($channelLink); ?></link>
    <description><?php echo e($channelDesc); ?></description>
    <item>
      <title>Demo item</title>
      <link>https://example.com/demo</link>
      <guid isPermaLink="false">demo-1</guid>
      <pubDate><?php echo gmdate('D, d M Y H:i:s') . ' GMT'; ?></pubDate>
      <description>Minimal RSS item for Static Guard compliance.</description>
    </item>
  </channel>
</rss>
