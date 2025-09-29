<?php

declare(strict_types=1);

use PU239\Config\ConfigRepository;
use Pu239\ImageProxy;

require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

require_once __DIR__ . '/../../include/bittorrent.php';

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

$imageProxy = $container->get(ImageProxy::class);
$imageProxy->optimize_image($path, '', false);

$imageUrl = (string) $config->get('paths.baseurl') . '/img.php?' . $pathlink;

json_out([
    'msg' => _('Success! Paste the following url to Poster.'),
    'url' => $imageUrl,
]);
