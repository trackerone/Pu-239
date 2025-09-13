<?php
declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

use Pu239\Database;
use Pu239\ImageProxy;

global $container;

$db = $container->get(Database::class);
set_time_limit(18000);
$image_proxy = $container->get(ImageProxy::class);
$path = IMAGES_DIR . 'proxy/';
$urls = $db->run('SELECT url FROM images')->fetchAll();

$photos = $db->run('SELECT photo AS url FROM person WHERE photo IS NOT NULL')->fetchAll();

$urls = array_merge($urls, $photos);

$images = [];
foreach ($urls as $url) {
    $images[] = PROXY_IMAGES_DIR . hash('sha256', $url['url']);
}
$filesize = $i = 0;
$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($objects as $name => $object) {
    if (!in_array($name, $images) && $name != $path . '.gitignore') {
        $filesize += filesize($name);
        ++$i;
        unlink($name);
    }
}

$set = [
    'fetched' => 'no',
    'updated' => 0,
    'checked' => 0,
];
$sql = 'UPDATE images SET fetched = :fetched, updated = :updated, checked = :checked WHERE added > 0';
$db->run($sql, [
    ':fetched' => 'no',
    ':updated' => 0,
    ':checked' => 0,
]);

echo "$i altered images removed
Images size: " . mksize($filesize) . "\n";
