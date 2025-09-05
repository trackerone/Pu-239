<?php
/**
 * Batch 37 – Auto-refactor FluentPDO to Aura.Sql
 * NOTE: Only handles simple ->from, ->insertInto, ->update, ->deleteFrom patterns.
 * Complex queries will need manual review.
 */

$root = dirname(__DIR__);
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (strpos($path, '/vendor/') !== false || strpos($path, '/node_modules/') !== false) continue;
    if (substr($path, -4) !== '.php') continue;

    $contents = file_get_contents($path);
    $orig = $contents;

    // Simple replacements
    $contents = preg_replace('/->from\(([^)]+)\).*?->fetchAll\(\)/s',
        '$sql = "SELECT * FROM $1"; $this->db->fetchAll($sql);',
        $contents);

    $contents = preg_replace('/->from\(([^)]+)\).*?->fetch\(\)/s',
        '$sql = "SELECT * FROM $1"; $this->db->fetchOne($sql);',
        $contents);

    $contents = preg_replace('/->insertInto\(([^,]+),\s*(\[.*?\])\)->execute\(\)/s',
        '$sql = "INSERT INTO $1 ..."; $this->db->perform($sql, $2);',
        $contents);

    $contents = preg_replace('/->update\(([^,]+),\s*(\[.*?\])\).*?->execute\(\)/s',
        '$sql = "UPDATE $1 SET ..."; $this->db->perform($sql, $2);',
        $contents);

    $contents = preg_replace('/->deleteFrom\(([^)]+)\).*?->execute\(\)/s',
        '$sql = "DELETE FROM $1 WHERE ..."; $this->db->perform($sql);',
        $contents);

    if ($contents !== $orig) {
        file_put_contents($path, $contents);
        echo "Refactored: $path\n";
    }
}
