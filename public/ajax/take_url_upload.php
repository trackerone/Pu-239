<?php
require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/mysql_compat.php';


declare(strict_types = 1);

use Pu239\ImageProxy;

require_once __DIR__ . '/../../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once INCL_DIR . 'function_password.php';
require_once INCL_DIR . 'function_bitbucket.php';
$user = check_user_status();
global $container, $site_config;

header('content-type: application/json');
if (empty($user['id'])) {
    echo json_encode(['msg' => _('Invalid ID')]);
    app_halt();
}

$url = $_POST['url'];
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['msg' => _('This does not appear to be a valid URL.')]);
    app_halt();
}
$username = $user['username'];
$SaLt = $site_config['salt']['one'];
$SaLty = $site_config['salt']['two'];
$skey = $site_config['salt']['three'];
$maxsize = $site_config['bucket']['maxsize'];
$folders = date('Y/m');
$formats = $site_config['images']['formats'];
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
    app_halt();
}
if (!file_put_contents($temppath, $image)) {
    echo json_encode(['msg' => _('There was an error trying to save the image to BitBucket.')]);
    app_halt();
}

$it1 = exif_imagetype($temppath);
if (!in_array($it1, $site_config['images']['exif'])) {
    echo json_encode(['msg' => _('Invalid file extension. jpg, gif, png and webp only.')]);
    app_halt();
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
    app_halt();
}

if (!file_exists($path)) {
    echo json_encode(['msg' => _('Upload failed to save image.')]);
    app_halt();
}
$image_proxy = $container->get(ImageProxy::class);
$image_proxy->optimize_image($path, '', false);
$image = "{$site_config['paths']['baseurl']}/img.php?{$pathlink}";

if (!empty($image)) {
    echo json_encode([
        'msg' => _('Success! Paste the following url to Poster.'),
        'url' => $image,
    ]);
    app_halt();
} else {
    echo json_encode(['msg' => _('Unknown failure occurred')]);
    app_halt();
}
