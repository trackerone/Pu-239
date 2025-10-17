<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=160 batch=5

namespace PU239\Http\Handlers\Public;

use DOMDocument;
use PU239\Config\ConfigRepository;
use Pu239\Cache;

final class RsstfreakHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=160 batch=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new \RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            check_user_status();

            $html = '';
            $useLimit = true;
            $limit = 15;
            $itemCount = 1;

            $xml = $cache->get('tfreaknewsrss_');
            if ($xml === false || $xml === null) {
                $xml = fetch('https://feeds.feedburner.com/Torrentfreak');
                $cache->set('tfreaknewsrss_', $xml, 300);
            }

            $document = new DOMDocument();
            @$document->loadXML((string) $xml);
            $items = $document->getElementsByTagName('item');

            foreach ($items as $item) {
                $titleNode = $item->getElementsByTagName('title')->item(0);
                $encodedNode = $item->getElementsByTagName('encoded')->item(0);

                $title = $titleNode?->nodeValue ?? '';
                $encoded = $encodedNode?->nodeValue ?? '';
                $content = preg_replace('/<p>Source:(.*?)width="1"\/>/is', '', $encoded) ?? '';

                $block = "
        <div class='has-text-left padding20'>
            <h2>{$title}</h2>
            <hr>{$content}
        </div>";
                $html .= main_div($block, $itemCount < $limit ? 'bottom20' : '');

                if ($useLimit && $itemCount++ >= $limit) {
                    break;
                }
            }

            $html = str_replace(['“', '”'], '"', $html);
            $html = str_replace(['’', '‘', '‘'], "'", $html);
            $html = str_replace('–', '-', $html);

            $anonymizerUrl = (string) $config->get('site.anonymizer_url');
            if ($anonymizerUrl !== '') {
                $html = str_replace('href="', 'href="' . $anonymizerUrl, $html);
            }

            $html = str_replace('="/images/', '="https://torrentfreak.com/images/', $html);
            $html = str_replace(['</img>', '<p> </p>', '<p></p>'], '', $html);

            preg_match_all('~<img.*?src=["\'](.*?)["\'].*?>~s', $html, $matches);
            foreach ($matches[1] as $match) {
                $html = str_replace($match, url_proxy($match, true), $html);
            }

            $title = _('TorrentFreak');
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
