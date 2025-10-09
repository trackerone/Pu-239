<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-09 via handler-convert batch=110-5

namespace PU239\Http\Handlers\Public\Ajax;

use PU239\Config\ConfigRepository;
use PU239\Security\UploadGuard;
use Pu239\ImageProxy;

final class TakeUploadHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-09 via handler-convert batch=110-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var ImageProxy $imageProxy */
            $imageProxy = $container->get(ImageProxy::class);

            $user = check_user_status();

            if (!isset($user['id'])) {
                json_out(['msg' => _('Invalid ID')]);
            }

            // TODO(2025): csrf
            $fileCount = (int) ($_POST['nbr_files'] ?? 0);

            if ($fileCount <= 0) {
                json_out(['msg' => _('No files selected')]);
            }

            $maxsize = (int) $config->get('bucket.maxsize');

            make_year(BITBUCKET_DIR);
            make_month(BITBUCKET_DIR);

            $images = [];

            for ($i = 0; $i < $fileCount; ++$i) {
                $file = $_FILES['file_' . $i] ?? null;
                if (!is_array($file)) {
                    continue;
                }

                $fileName = (string) ($file['name'] ?? '');
                $tmpName = (string) ($file['tmp_name'] ?? '');
                $fileSize = (int) ($file['size'] ?? 0);

                if ($fileName === '' || $tmpName === '') {
                    continue;
                }

                if ($maxsize > 0 && $fileSize > $maxsize) {
                    json_out(['msg' => _('File exceeds the maximum allowed size.')]);
                }

                $type = @exif_imagetype($tmpName);

                if ($type === false || !in_array($type, (array) $config->get('images.exif'), true)) {
                    json_out(['msg' => _('Invalid file extension. jpg, gif, png and webp only.')]);
                }

                $options = [
                    'allow_ext' => 'jpg,jpeg,png,webp,gif',
                    'storage' => rtrim(BITBUCKET_DIR, '/\\'),
                ];
                if ($maxsize > 0) {
                    $options['max_bytes'] = $maxsize;
                }

                try {
                    $upload = UploadGuard::store($file, $options);
                } catch (\Throwable $e) {
                    if (!headers_sent()) {
                        header('Content-Type: application/json; charset=utf-8');
                    }
                    $code = http_response_code();
                    if (!in_array($code, [400, 413, 415], true)) {
                        http_response_code(500);
                    }
                    exit(json_encode(['msg' => 'Upload rejected: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
                }

                $path = rtrim(BITBUCKET_DIR, '/\\') . '/' . $upload['path'];
                $imageProxy->optimize_image($path, '', false);
                $images[] = (string) $config->get('paths.baseurl') . '/img.php?' . $upload['path'];
            }

            if ($images !== []) {
                json_out([
                    'msg' => _('Success! Paste the following url to Poster.'),
                    'urls' => $images,
                ]);
            }

            json_out(['msg' => _('Unknown failure occurred')]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
