<?php
declare(strict_types=1);

use PU239\Config\ConfigRepository;

$root = dirname(__DIR__);
require_once $root . '/bootstrap.php';

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$cacheDir = (string) ($config->get('paths.cache') ?? ($root . '/storage/cache'));
$keep = ['.gitignore', 'README.md'];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
$n = 0;
foreach ($it as $f) {
    if (in_array($f->getFilename(), $keep, true)) {
        continue;
    }
    $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    $n++;
}
echo "Cache cleared: {$n} items\n";
