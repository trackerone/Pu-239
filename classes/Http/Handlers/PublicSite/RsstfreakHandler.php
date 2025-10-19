<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T16:48:06Z via handler-convert offset=285 batch=5

namespace PU239\Http\Handlers\PublicSite;

use DOMDocument;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;

use function dirname;
use function htmlspecialchars;
use function is_string;
use function preg_match_all;
use function preg_replace;
use function sprintf;
use function str_replace;

final class RsstfreakHandler
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

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            unset($db); // legacy script fetched a database instance but performed no queries
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            check_user_status();

            $html = '';
            $useLimit = true;
            $limit = 15;
            $itemCount = 1;

            $cacheKey = 'tfreaknewsrss_';
            $xml = $cache->get($cacheKey);
            if ($xml === false || $xml === null) {
                $xml = fetch('https://feeds.feedburner.com/Torrentfreak');
                if (is_string($xml)) {
                    $cache->set($cacheKey, $xml, 300);
                } else {
                    $xml = '';
                }
            }

            $document = new DOMDocument();
            if ($xml !== '' && @$document->loadXML($xml) !== false) {
                $items = $document->getElementsByTagName('item');
                foreach ($items as $item) {
                    $titleNode = $item->getElementsByTagName('title')->item(0);
                    $contentNode = $item->getElementsByTagName('encoded')->item(0);
                    $title = $titleNode?->nodeValue ?? '';
                    $content = $contentNode?->nodeValue ?? '';
                    if ($title === '' && $content === '') {
                        continue;
                    }

                    $sanitizedContent = preg_replace(
                        "@<p>Source:(.*?)width=\"1\"/>@is",
                        '',
                        (string) $content
                    );
                    $div = sprintf(
                        "        <div class='has-text-left padding20'>\n            <h2>%s</h2>\n            <hr>%s\n        </div>",
                        $title,
                        $sanitizedContent
                    );
                    $html .= main_div($div, $itemCount < $limit ? 'bottom20' : '');
                    if ($useLimit && $itemCount++ >= $limit) {
                        break;
                    }
                }
            }

            $html = str_replace([
                '“',
                '”',
            ], '"', $html);
            $html = str_replace([
                '’',
                '‘',
            ], "'", $html);
            $html = str_replace('–', '-', $html);

            $anonymizerUrl = (string) $config->get('site.anonymizer_url');
            if ($anonymizerUrl !== '') {
                $html = str_replace('href="', 'href="' . $anonymizerUrl, $html);
            }
            $html = str_replace('="/images/', '="https://torrentfreak.com/images/', $html);
            $html = str_replace([
                '</img>',
                '<p> </p>',
                '<p></p>',
            ], '', $html);

            preg_match_all('/<img.*?src=["\'](.*?)["\'].*?>/s', $html, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    $html = str_replace($match, url_proxy($match, true), $html);
                }
            }

            $title = _('TorrentFreak');
            $breadcrumbs = [
                sprintf("<a href='%s'>%s</a>", $_SERVER['PHP_SELF'] ?? '', $title),
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
