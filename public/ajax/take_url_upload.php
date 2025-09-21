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

$url = $_POST['url'];
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['msg' => _('This does not appear to be a valid URL.')]);
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
$rand = make_password();
$temppath = CACHE_DIR . $rand;
make_year(BITBUCKET_DIR);
make_month(BITBUCKET_DIR);

$image = fetch($url);
if (!$image) {
    echo json_encode(['msg' => _('There was an error trying to fetch the image.')]);
    app_halt('Exit called');
}
if (!file_put_contents($temppath, $image)) {
    echo json_encode(['msg' => _('There was an error trying to save the image to BitBucket.')]);
    app_halt('Exit called');
}

$it1 = exif_imagetype($temppath);
if (!in_array($it1, (array) $config->get('images.exif'))) {
    echo json_encode(['msg' => _('Invalid file extension. jpg, gif, png and webp only.')]);
    app_halt('Exit called');
}
switch ($it1) {
    case 1:
        $ext = '.gif';
        break;
    case 2:
        $ext = '.jpg';
        break;
    case 3:
        $ext = '.png';
        break;
    case 19:
        $ext = '.webp';
        break;
}

$path = $bucketdir . $USERSALT . '_' . $rand . $ext;
$pathlink = $bucketlink . $USERSALT . '_' . $rand . $ext;
if (!rename($temppath, $path)) {
    echo json_encode(['msg' => _('Upload failed to save image.')]);
    app_halt('Exit called');
}

if (!file_exists($path)) {
    echo json_encode(['msg' => _('Upload failed to save image.')]);
    app_halt('Exit called');
}
$image_proxy = $container->get(ImageProxy::class);
$image_proxy->optimize_image($path, '', false);
$image = (string) $config->get('paths.baseurl') . '/img.php?' . $pathlink;

if (!empty($image)) {
    echo json_encode([
        'msg' => _('Success! Paste the following url to Poster.'),
        'url' => $image,
    ]);
    app_halt('Exit called');
} else {
    echo json_encode(['msg' => _('Unknown failure occurred')]);
    app_halt('Exit called');
}
