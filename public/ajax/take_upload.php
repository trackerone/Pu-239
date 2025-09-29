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
$fileCount = (int) ($_POST['nbr_files'] ?? 0);

if ($fileCount <= 0) {
    json_out(['msg' => _('No files selected')]);
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
        json_out(['msg' => _('File exceeds the maximum allowed size.')]);
    }

    $cleanName = preg_replace('`[^a-z0-9\-_.]`i', '', $fileName);
    $type = @exif_imagetype($tmpName);

    if ($type === false || !in_array($type, (array) $config->get('images.exif'), true)) {
        json_out(['msg' => _('Invalid file extension. jpg, gif, png and webp only.')]);
    }

    $cleanName = strtolower($cleanName ?? '');
    $random = make_password();
    $path = $bucketdir . $USERSALT . '_' . $random . $cleanName;
    $pathlink = $bucketlink . $USERSALT . '_' . $random . $cleanName;

    if (!move_uploaded_file($tmpName, $path)) {
        json_out(['msg' => _('Upload failed to save image.')]);
    }

    if (!file_exists($path)) {
        json_out(['msg' => _('Upload failed to save image.')]);
    }

    $imageProxy->optimize_image($path, '', false);
    $images[] = (string) $config->get('paths.baseurl') . '/img.php?' . $pathlink;
}

if ($images !== []) {
    json_out([
        'msg' => _('Success! Paste the following url to Poster.'),
        'urls' => $images,
    ]);
}

json_out(['msg' => _('Unknown failure occurred')]);
