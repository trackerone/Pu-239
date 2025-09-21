<?php
declare(strict_types=1);

use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\ImageProxy;

require_once dirname(__DIR__) . '/bootstrap_web.php';

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();

header('content-type: application/json');
if (empty($user['id'])) {
    echo json_encode(['msg' => _('Invalid ID')]);
    app_halt('Exit called');
}
$username = $user['username'];
$SaLt = (string) $config->get('salt.one');
$SaLty = (string) $config->get('salt.two');
$skey = (string) $config->get('salt.three');
$maxsize = (int) $config->get('bucket.maxsize');
$folders = date('Y/m');
$formats = (array) $config->get('images.formats');
$str = implode('|', $formats);
$bucketdir = BITBUCKET_DIR . $folders . '/';
$bucketlink = $folders . '/';
$PICSALT = $SaLt . $username;
$USERSALT = substr(md5($SaLty . $user['id']), 0, 6);
make_year(BITBUCKET_DIR);
make_month(BITBUCKET_DIR);

$image_proxy = $container->get(ImageProxy::class);
for ($i = 0; $i < $_POST['nbr_files']; ++$i) {
    $file = preg_replace('`[^a-z0-9\-\_\.]`i', '', $_FILES['file_' . $i]['name']);
    $it1 = exif_imagetype($_FILES['file_' . $i]['tmp_name']);
    if (!in_array($it1, (array) $config->get('images.exif'))) {
        echo json_encode(['msg' => _('Invalid file extension. jpg, gif, png and webp only.')]);
        app_halt('Exit called');
    }

    $file = strtolower($file);
    $randb = make_password();
    $path = $bucketdir . $USERSALT . '_' . $randb . $file;
    $pathlink = $bucketlink . $USERSALT . '_' . $randb . $file;
    if (!move_uploaded_file($_FILES['file_' . $i]['tmp_name'], $path)) {
        echo json_encode(['msg' => _('Upload failed to save image.')]);
        app_halt('Exit called');
    }

    if (!file_exists($path)) {
        echo json_encode(['msg' => _('Upload failed to save image.')]);
        app_halt('Exit called');
    }
    $image_proxy->optimize_image($path, '', false);
    $images[] = (string) $config->get('paths.baseurl') . '/img.php?' . $pathlink;
}

if (!empty($images)) {
    $output = [
        'msg' => _('Success! Paste the following url to Poster.'),
        'urls' => $images,
    ];
    echo json_encode($output);
    app_halt('Exit called');
} else {
    echo json_encode(['msg' => _('Unknown failure occurred')]);
    app_halt('Exit called');
}
