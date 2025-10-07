<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=80-5

namespace PU239\Http\Handlers\Public;

use Pu239\Database;

final class RssPdoDemoHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=80-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            global $container;
            /** @var Database $db */
            $db = $container->get(Database::class);
            unset($db); // Intentional: demo handler retains bootstrap side-effects.

            header('Content-Type: application/rss+xml; charset=UTF-8');

            $channelTitle = 'Pu-239 RSS Demo';
            $channelLink = 'https://example.com/';
            $channelDesc = 'Demo feed without short open tags';

            $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            echo '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\n";
            echo "<rss version=\"2.0\">\n";
            echo "  <channel>\n";
            echo '    <title>' . $escape($channelTitle) . "</title>\n";
            echo '    <link>' . $escape($channelLink) . "</link>\n";
            echo '    <description>' . $escape($channelDesc) . "</description>\n";
            echo "    <item>\n";
            echo "      <title>Demo item</title>\n";
            echo "      <link>https://example.com/demo</link>\n";
            echo "      <guid isPermaLink=\"false\">demo-1</guid>\n";
            echo '      <pubDate>' . gmdate('D, d M Y H:i:s') . " GMT</pubDate>\n";
            echo "      <description>Minimal RSS item for Static Guard compliance.</description>\n";
            echo "    </item>\n";
            echo "  </channel>\n";
            echo "</rss>\n";
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
