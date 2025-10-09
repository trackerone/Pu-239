<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-09 via handler-convert batch=110-5

namespace PU239\Http\Handlers\Public\Ajax;

use PU239\Config\ConfigRepository;
use Pu239\ImageProxy;

final class TakeUrlUploadHandler
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
            $url = (string) ($_POST['url'] ?? '');

            if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
                json_out(['msg' => _('This does not appear to be a valid URL.')]);
            }

            $SaLty = (string) $config->get('salt.two');
            $folders = date('Y/m');
            $bucketdir = BITBUCKET_DIR . $folders . '/';
            $bucketlink = $folders . '/';
            $USERSALT = substr(md5($SaLty . $user['id']), 0, 6);
            $rand = make_password();
            $temppath = CACHE_DIR . $rand;

            make_year(BITBUCKET_DIR);
            make_month(BITBUCKET_DIR);

            $image = fetch($url);

            if ($image === false) {
                json_out(['msg' => _('There was an error trying to fetch the image.')]);
            }

            if (@file_put_contents($temppath, $image) === false) {
                json_out(['msg' => _('There was an error trying to save the image to BitBucket.')]);
            }

            $type = @exif_imagetype($temppath);

            if ($type === false || !in_array($type, (array) $config->get('images.exif'), true)) {
                @unlink($temppath);
                json_out(['msg' => _('Invalid file extension. jpg, gif, png and webp only.')]);
            }

            $extension = match ($type) {
                IMAGETYPE_GIF => '.gif',
                IMAGETYPE_JPEG => '.jpg',
                IMAGETYPE_PNG => '.png',
                IMAGETYPE_WEBP => '.webp',
                default => '',
            };

            if ($extension === '') {
                @unlink($temppath);
                json_out(['msg' => _('Invalid file extension. jpg, gif, png and webp only.')]);
            }

            $path = $bucketdir . $USERSALT . '_' . $rand . $extension;
            $pathlink = $bucketlink . $USERSALT . '_' . $rand . $extension;

            if (!@rename($temppath, $path)) {
                @unlink($temppath);
                json_out(['msg' => _('Upload failed to save image.')]);
            }

            if (!file_exists($path)) {
                json_out(['msg' => _('Upload failed to save image.')]);
            }

            $imageProxy->optimize_image($path, '', false);

            $imageUrl = (string) $config->get('paths.baseurl') . '/img.php?' . $pathlink;

            json_out([
                'msg' => _('Success! Paste the following url to Poster.'),
                'url' => $imageUrl,
            ]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
