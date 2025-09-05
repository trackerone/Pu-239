<?php
/**
 * Batch 37.3 – Auto-refactor FluentPDO to Aura.Sql
 * Converts common patterns into Aura.Sql skeletons.
 * Adds TODO where manual review is needed.
 */

$root = dirname(__DIR__);
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$totalChanges = 0;
$log = [];

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (strpos($path, '/vendor/') !== false || strpos($path, '/node_modules/') !== false) continue;
    if (substr($path, -4) !== '.php') continue;

    $contents = file_get_contents($path);
    $orig = $contents;

    // Remove Literal imports
    $contents = preg_replace('/^use\s+Envms\\\\FluentPDO\\\\Literal;\s*$/m', '// removed FluentPDO Literal', $contents);

    // FROM … fetchAll
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->from\([^)]+\).*?->fetchAll\(\)/s',
        '// TODO: review query' . "\n" .
        '$sql = "SELECT * FROM table WHERE ...";' . "\n" .
        '$this->db->fetchAll($sql, [/* params */]);',
        $contents
    );

    // FROM … fetchOne
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->from\([^)]+\).*?->fetch\(\)/s',
        '// TODO: review query' . "\n" .
        '$sql = "SELECT * FROM table WHERE ...";' . "\n" .
        '$this->db->fetchOne($sql, [/* params */]);',
        $contents
    );

    // INSERT
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->insertInto\([^)]+\).*?->execute\(\)/s',
        '// TODO: review insert' . "\n" .
        '$sql = "INSERT INTO table (...) VALUES (...)";' . "\n" .
        '$this->db->perform($sql, [/* params */]);',
        $contents
    );

    // UPDATE
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->update\([^)]+\).*?->execute\(\)/s',
        '// TODO: review update' . "\n" .
        '$sql = "UPDATE table SET ... WHERE ...";' . "\n" .
        '$this->db->perform($sql, [/* params */]);',
        $contents
    );

    // DELETE
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->deleteFrom\([^)]+\).*?->execute\(\)/s',
        '// TODO: review delete' . "\n" .
        '$sql = "DELETE FROM table WHERE ...";' . "\n" .
        '$this->db->perform($sql, [/* params */]);',
        $contents
    );

    if ($contents !== $orig) {
        file_put_contents($path, $contents);
        $log[] = "Refactored: $path";
        $totalChanges++;
    }
}

file_put_contents("refactor-log.txt", implode("\n", $log) . "\nTotal files changed: $totalChanges\n");
echo "Total files changed: $totalChanges\n";
