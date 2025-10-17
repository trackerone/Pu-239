<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=160 batch=5

namespace PU239\Http\Handlers\Public;

final class ImgHandler
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

            if (!isset($_SERVER['REQUEST_URI'])) {
                return;
            }

            $imagePath = $this->validPath(BITBUCKET_DIR, $_SERVER['QUERY_STRING'] ?? '');
            if ($imagePath === null || $imagePath === '') {
                $imagePath = IMAGES_DIR . 'noposter.png';
            }

            $pathInfo = pathinfo($imagePath);
            if (empty($pathInfo['extension']) || !preg_match('#^(jpg|jpeg|gif|png)$#i', (string) $pathInfo['extension'])) {
                $imagePath = IMAGES_DIR . 'noposter.png';
                $pathInfo = pathinfo($imagePath);
            }

            $lastModified = filemtime($imagePath) ?: TIME_NOW;
            $dateFormat = 'D, d M Y H:i:s T';
            $expires = date($dateFormat, time() + (86400 * 7));
            $lastModifiedHeader = date($dateFormat, $lastModified);

            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $since = explode(';', (string) $_SERVER['HTTP_IF_MODIFIED_SINCE'], 2);
                $sinceTime = strtotime($since[0]);
                if ($sinceTime !== false && $sinceTime === $lastModified) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
                    header('Expires: ' . $expires);
                    header('Cache-Control: private, max-age=604800');
                    app_halt('Exit called');
                }
            }

            header('Expires: ' . $expires);
            header('Cache-Control: private, max-age=604800');
            header('Last-Modified: ' . $lastModifiedHeader);
            header('Content-type: image/' . strtolower((string) $pathInfo['extension']));
            readfile($imagePath);
            app_halt('Exit called');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    private function validPath(string $root, string $input): ?string
    {
        $fullPath = $root . str_replace('%E2%80%8B', '', $input);
        $realPath = realpath($fullPath);

        return $realPath !== false ? $realPath : null;
    }
}
