<?php
require_once __DIR__ . '/runtime_safe.php';


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
make_year(BITBUCKET_DIR);
make_month(BITBUCKET_DIR);

$image_proxy = $container->get(ImageProxy::class);
for ($i = 0; $i < $_POST['nbr_files']; ++$i) {
    $file = preg_replace('`[^a-z0-9\-\_\.]`i', '', $_FILES['file_' . $i]['name']);
    $it1 = exif_imagetype($_FILES['file_' . $i]['tmp_name']);
    if (!in_array($it1, $site_config['images']['exif'])) {
        echo json_encode(['msg' => _('Invalid file extension. jpg, gif, png and webp only.')]);
        app_halt();
    }

    $file = strtolower($file);
    $randb = make_password();
    $path = $bucketdir . $USERSALT . '_' . $randb . $file;
    $pathlink = $bucketlink . $USERSALT . '_' . $randb . $file;
    if (!move_uploaded_file($_FILES['file_' . $i]['tmp_name'], $path)) {
        echo json_encode(['msg' => _('Upload failed to save image.')]);
        app_halt();
    }

    if (!file_exists($path)) {
        echo json_encode(['msg' => _('Upload failed to save image.')]);
        app_halt();
    }
    $image_proxy->optimize_image($path, '', false);
    $images[] = "{$site_config['paths']['baseurl']}/img.php?{$pathlink}";
}

if (!empty($images)) {
    $output = [
        'msg' => _('Success! Paste the following url to Poster.'),
        'urls' => $images,
    ];
    echo json_encode($output);
    app_halt();
} else {
    echo json_encode(['msg' => _('Unknown failure occurred')]);
    app_halt();
}
