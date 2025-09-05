<?php
/**
 * Batch 37.4 – Safe FluentPDO refactor
 * Converts simple patterns (from+where, insert, update, delete).
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

    // FROM ... WHERE id
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->from\(\'([a-zA-Z0-9_]+)\'\).*?->where\(\'([a-zA-Z0-9_]+)\',\s*(\$[a-zA-Z0-9_]+)\).*?->fetchAll\(\)/s',
        '$sql = "SELECT * FROM $2 WHERE $3 = :$3";' . "\n" .
        '$result = $this->db->fetchAll($sql, [\'$3\' => $4]);',
        $contents
    );

    // INSERT simple
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->insertInto\(\'([a-zA-Z0-9_]+)\'\).*?->execute\(\)/s',
        '$sql = "INSERT INTO $2 (...) VALUES (...)";' . "\n" .
        '$this->db->perform($sql, [/* params */]);',
        $contents
    );

    // UPDATE simple
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->update\(\'([a-zA-Z0-9_]+)\'\).*?->execute\(\)/s',
        '$sql = "UPDATE $2 SET ... WHERE ...";' . "\n" .
        '$this->db->perform($sql, [/* params */]);',
        $contents
    );

    // DELETE simple
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->deleteFrom\(\'([a-zA-Z0-9_]+)\'\).*?->execute\(\)/s',
        '$sql = "DELETE FROM $2 WHERE ...";' . "\n" .
        '$this->db->perform($sql, [/* params */]);',
        $contents
    );

    if ($contents !== $orig) {
        file_put_contents($path, $contents);
        echo "Refactored safe: $path\n";
    }
}
