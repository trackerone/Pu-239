<?php

declare(strict_types=1);

use PU239\Config\ConfigRepository;
use Pu239\ImageProxy;

require_once dirname(__DIR__) . '/bootstrap_web.php';

/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

require_once __DIR__ . '/../../include/bittorrent.php';

$user = check_user_status();

header('Content-Type: application/json; charset=utf-8');

if (!isset($user['id'])) {
    echo json_encode(['msg' => _('Invalid ID')], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

// TODO(2025): csrf
$url = (string) ($_POST['url'] ?? '');

if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
    echo json_encode(['msg' => _('This does not appear to be a valid URL.')], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
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
    echo json_encode(['msg' => _('There was an error trying to fetch the image.')], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

if (@file_put_contents($temppath, $image) === false) {
    echo json_encode(['msg' => _('There was an error trying to save the image to BitBucket.')], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

$type = @exif_imagetype($temppath);

if ($type === false || !in_array($type, (array) $config->get('images.exif'), true)) {
    @unlink($temppath);
    echo json_encode(['msg' => _('Invalid file extension. jpg, gif, png and webp only.')], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
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
    echo json_encode(['msg' => _('Invalid file extension. jpg, gif, png and webp only.')], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

$path = $bucketdir . $USERSALT . '_' . $rand . $extension;
$pathlink = $bucketlink . $USERSALT . '_' . $rand . $extension;

if (!@rename($temppath, $path)) {
    @unlink($temppath);
    echo json_encode(['msg' => _('Upload failed to save image.')], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

if (!file_exists($path)) {
    echo json_encode(['msg' => _('Upload failed to save image.')], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

$imageProxy = $container->get(ImageProxy::class);
$imageProxy->optimize_image($path, '', false);

$imageUrl = (string) $config->get('paths.baseurl') . '/img.php?' . $pathlink;

echo json_encode([
    'msg' => _('Success! Paste the following url to Poster.'),
    'url' => $imageUrl,
], JSON_THROW_ON_ERROR);
app_halt('Exit called');
