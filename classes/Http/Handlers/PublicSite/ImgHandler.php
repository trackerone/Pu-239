<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:47:17Z via handler-convert offset=185 size=5

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Database;
use RuntimeException;

final class ImgHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:47:17Z via handler-convert offset=185 size=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var Database $db */
            $db = $container->get(Database::class);
            unset($db); // legacy script fetched the database but performed no queries

            $queryString = $_SERVER['QUERY_STRING'] ?? '';
            $image = $this->resolvePath(BITBUCKET_DIR, $queryString);
            if ($image === null) {
                $image = IMAGES_DIR . 'noposter.png';
            }

            $pathInfo = @pathinfo($image);
            if (empty($pathInfo['extension']) || !preg_match('#^(jpg|jpeg|gif|png)$#i', (string) $pathInfo['extension'])) {
                $image = IMAGES_DIR . 'noposter.png';
                $pathInfo = @pathinfo($image);
            }

            $lastModified = filemtime($image);
            $dateFormat = 'D, d M Y H:i:s T';
            $lastModifiedDate = date($dateFormat, (int) $lastModified);
            $expiresDate = date($dateFormat, time() + (86400 * 7));

            $sendBody = true;
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $parts = explode(';', (string) $_SERVER['HTTP_IF_MODIFIED_SINCE'], 2);
                $since = strtotime($parts[0]);
                if ($since !== false && $since === $lastModified) {
                    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 304 Not Modified');
                    $sendBody = false;
                }
            }

            header('Expires: ' . $expiresDate);
            header('Cache-Control: private, max-age=604800');
            if (!$sendBody) {
                app_halt('Exit called');
            }

            $extension = (string) ($pathInfo['extension'] ?? '');
            header('Last-Modified: ' . $lastModifiedDate);
            header('Content-type: image/' . strtolower($extension));
            readfile($image);
            app_halt('Exit called');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    private function resolvePath(string $root, string $input): ?string
    {
        $full = $root . str_replace('%E2%80%8B', '', $input);
        $resolved = realpath($full);

        return $resolved !== false ? $resolved : null;
    }
}
