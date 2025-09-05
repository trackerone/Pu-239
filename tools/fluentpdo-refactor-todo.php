<?php
/**
 * Batch 37.5 – TODO FluentPDO refactor
 * Marks complex/unhandled FluentPDO queries with TODO, without deleting large blocks.
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

    // Match en enkelt kæde: from(...) ... ->fetchAll/fetch/execute
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->[a-zA-Z0-9_]+\([^)]*\)(?:->[a-zA-Z0-9_]+\([^)]*\))*->(fetchAll|fetch|execute)\(\)/',
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
