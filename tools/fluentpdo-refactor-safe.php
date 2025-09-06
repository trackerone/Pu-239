<?php
/**
 * Batch 37.7 – Safe FluentPDO refactor
 * Konverterer inserts/updates/deletes fra FluentPDO til Aura.Sql skeletter,
 * bevarer variabler ($values, $set, $params[...] osv.)
 * og tilføjer semikolon korrekt.
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

    // INSERT
    $contents = preg_replace(
        '/(\$[a-zA-Z0-9_]+\s*=\s*)?(?:\$this->fluent|\$fluent)->insertInto\(\'([a-zA-Z0-9_]+)\'\)\s*->values\((\$[a-zA-Z0-9_]+)\)\s*->execute\(\)/s',
        '$sql = "INSERT INTO $2 (/* columns */) VALUES (/* values */)";' . "\n" .
        '$1$this->db->perform($sql, $3);',
        $contents
    );

    // DELETE
    $contents = preg_replace(
        '/(\$[a-zA-Z0-9_]+\s*=\s*)?(?:\$this->fluent|\$fluent)->deleteFrom\(\'([a-zA-Z0-9_]+)\'\)\s*->where\(\'([a-zA-Z0-9_]+)\s*=\s*\?\',\s*(\$[a-zA-Z0-9_\[\]\'"]+)\)\s*->execute\(\)/s',
        '$sql = "DELETE FROM $2 WHERE $3 = :$3";' . "\n" .
        '$1$this->db->perform($sql, [\'$3\' => $4]);',
        $contents
    );

    // UPDATE med WHERE
    $contents = preg_replace(
        '/(\$[a-zA-Z0-9_]+\s*=\s*)?(?:\$this->fluent|\$fluent)->update\(\'([a-zA-Z0-9_]+)\'\)\s*->set\((\$[a-zA-Z0-9_]+)\)\s*->where\(\'([a-zA-Z0-9_]+)\s*=\s*\?\',\s*(\$[a-zA-Z0-9_\[\]\'"]+)\)\s*->execute\(\)/s',
        '$sql = "UPDATE $2 SET /* columns */ WHERE $4 = :$4";' . "\n" .
        '$1$this->db->perform($sql, array_merge($3, [\'$4\' => $5]));',
        $contents
    );

    // UPDATE uden WHERE
    $contents = preg_replace(
        '/(\$[a-zA-Z0-9_]+\s*=\s*)?(?:\$this->fluent|\$fluent)->update\(\'([a-zA-Z0-9_]+)\'\)\s*->set\((\$[a-zA-Z0-9_]+)\)\s*->execute\(\)/s',
        '$sql = "UPDATE $2 SET /* columns */";' . "\n" .
        '$1$this->db->perform($sql, $3);',
        $contents
    );

    if ($contents !== $orig) {
        file_put_contents($path, $contents);
        echo "Refactored safe: $path\n";
    }
}
