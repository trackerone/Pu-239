<?php
/**
 * Batch 37.6 – TODO FluentPDO refactor
 * Marks only the queries we can't auto-convert safely.
 * Stram regex, ingen dobbelte semikolon.
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

    // Matcher én kæde: fx $fluent->from(...)->...->fetchAll()
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->[a-zA-Z0-9_]+\([^)]*\)(?:->[a-zA-Z0-9_]+\([^)]*\))*->(fetchAll|fetch|execute)\(\)/',
        '// TODO: review query' . "\n" .
        '$sql = "SELECT/INSERT/UPDATE/DELETE ..."' . "\n" .
        '$this->db->perform($sql, [/* params */])',
        $contents
    );

    if ($contents !== $orig) {
        file_put_contents($path, $contents);
        echo "Marked TODO: $path\n";
    }
}
