<?php
/**
 * Batch 37.4 – TODO FluentPDO refactor
 * Marks complex patterns for manual review.
 */

$root = dirname(__DIR__);
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (strpos($path, '/vendor/') !== false) continue;
    if (substr($path, -4) !== '.php') continue;

    $contents = file_get_contents($path);
    $orig = $contents;

    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->.*?->(fetchAll|fetch|execute)\(\)/s',
        '// TODO: review query' . "\n" .
        '$sql = "SELECT/INSERT/UPDATE/DELETE ...";' . "\n" .
        '$this->db->perform($sql, [/* params */]);',
        $contents
    );

    if ($contents !== $orig) {
        file_put_contents($path, $contents);
        echo "Marked TODO: $path\n";
    }
}
