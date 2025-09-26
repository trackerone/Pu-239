<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Config\ConfigRepository;
use Pu239\ImageProxy;

<<<<<< codex/enforce-csrf-and-escape-output-dxtuor
=======
require_once dirname(__DIR__) . '/bootstrap_web.php';

>>>>>> master
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
$fileCount = (int) ($_POST['nbr_files'] ?? 0);

if ($fileCount <= 0) {
    echo json_encode(['msg' => _('No files selected')], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

$SaLty = (string) $config->get('salt.two');
$maxsize = (int) $config->get('bucket.maxsize');
$folders = date('Y/m');
$bucketdir = BITBUCKET_DIR . $folders . '/';
$bucketlink = $folders . '/';
$USERSALT = substr(md5($SaLty . $user['id']), 0, 6);

make_year(BITBUCKET_DIR);
make_month(BITBUCKET_DIR);

$imageProxy = $container->get(ImageProxy::class);
$images = [];

for ($i = 0; $i < $fileCount; ++$i) {
    $fileName = $_FILES['file_' . $i]['name'] ?? '';
    $tmpName = $_FILES['file_' . $i]['tmp_name'] ?? '';
    $fileSize = (int) ($_FILES['file_' . $i]['size'] ?? 0);

    if ($fileName === '' || $tmpName === '') {
        continue;
    }

    if ($maxsize > 0 && $fileSize > $maxsize) {
        echo json_encode(['msg' => _('File exceeds the maximum allowed size.')], JSON_THROW_ON_ERROR);
        app_halt('Exit called');
    }

    $cleanName = preg_replace('`[^a-z0-9\-_.]`i', '', $fileName);
    $type = @exif_imagetype($tmpName);

    if ($type === false || !in_array($type, (array) $config->get('images.exif'), true)) {
        echo json_encode(['msg' => _('Invalid file extension. jpg, gif, png and webp only.')], JSON_THROW_ON_ERROR);
        app_halt('Exit called');
    }

    $cleanName = strtolower($cleanName ?? '');
    $random = make_password();
    $path = $bucketdir . $USERSALT . '_' . $random . $cleanName;
    $pathlink = $bucketlink . $USERSALT . '_' . $random . $cleanName;

    if (!move_uploaded_file($tmpName, $path)) {
        echo json_encode(['msg' => _('Upload failed to save image.')], JSON_THROW_ON_ERROR);
        app_halt('Exit called');
    }

    if (!file_exists($path)) {
        echo json_encode(['msg' => _('Upload failed to save image.')], JSON_THROW_ON_ERROR);
        app_halt('Exit called');
    }

    $imageProxy->optimize_image($path, '', false);
    $images[] = (string) $config->get('paths.baseurl') . '/img.php?' . $pathlink;
}

if ($images !== []) {
    echo json_encode([
        'msg' => _('Success! Paste the following url to Poster.'),
        'urls' => $images,
    ], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

echo json_encode(['msg' => _('Unknown failure occurred')], JSON_THROW_ON_ERROR);
app_halt('Exit called');
