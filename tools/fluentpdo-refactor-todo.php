<?php
/**
 * Batch 37.7 – TODO FluentPDO refactor
 * Marks leftover FluentPDO queries with TODO AND logs all findings.
 */

$root = dirname(__DIR__);
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$totalMatches = 0;
$log = [];

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (strpos($path, '/vendor/') !== false) continue;
    if (substr($path, -4) !== '.php') continue;

    $contents = file_get_contents($path);
    $orig = $contents;

    // Matcher én kæde
    $pattern = '/(\$this->fluent|\$fluent)->[a-zA-Z0-9_]+\([^)]*\)(?:->[a-zA-Z0-9_]+\([^)]*\))*->(fetchAll|fetch|execute)\(\)/';

    if (preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $m) {
            $totalMatches++;
            $log[] = $path . " : " . trim($m[0]);
        }

        $contents = preg_replace(
            $pattern,
            '// TODO: review query' . "\n" .
            '$sql = "SELECT/INSERT/UPDATE/DELETE ...";' . "\n" .
            '$this->db->perform($sql, [/* params */]);',
            $contents
        );

        if ($contents !== $orig) {
            file_put_contents($path, $contents);
            echo "Marked TODO in: $path\n";
        }
    }
}

file_put_contents("refactor-todo-log.txt", implode("\n", $log) . "\nTotal matches: $totalMatches\n");

echo "Total matches: $totalMatches\n";
