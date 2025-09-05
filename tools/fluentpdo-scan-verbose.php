<?php
/**
 * Batch 37.2 – Scan for FluentPDO usages with verbose logging
 * Does NOT modify files – just logs what it finds.
 */

$root = dirname(__DIR__);
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$total = 0;
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (strpos($path, '/vendor/') !== false || strpos($path, '/node_modules/') !== false) continue;
    if (substr($path, -4) !== '.php') continue;

    $lines = file($path);
    $matches = 0;
    foreach ($lines as $num => $line) {
        if (strpos($line, '->from(') !== false ||
            strpos($line, '->insertInto(') !== false ||
            strpos($line, '->update(') !== false ||
            strpos($line, '->deleteFrom(') !== false ||
            strpos($line, 'FluentPDO') !== false) {
            $matches++;
            $total++;
            echo sprintf("[%s:%d] %s\n", $path, $num+1, trim($line));
        }
    }
    if ($matches > 0) {
        echo "---- Found $matches matches in $path ----\n\n";
    }
}

echo "============================\n";
echo "Total matches across repo: $total\n";
