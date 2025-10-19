<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T16:48:06Z via handler-convert offset=285 batch=5

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

use function dirname;
use function gmdate;
use function htmlspecialchars;

final class RssPdoDemoHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T16:48:06Z via handler-convert offset=285 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            unset($db); // legacy script touched the database but issued no queries

            header('Content-Type: application/rss+xml; charset=UTF-8');

            $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // Construct XML declaration without short open tags to avoid compatibility issues
            echo '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\n";

            $channelTitle = 'Pu-239 RSS Demo';
            $channelLink = 'https://example.com/';
            $channelDesc = 'Demo feed without short open tags';

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
