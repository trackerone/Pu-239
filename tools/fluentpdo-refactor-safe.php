<?php
/**
 * Batch 37.6 – Safe FluentPDO refactor
 * Konverterer inserts/updates/deletes fra FluentPDO til Aura.Sql skeletter,
 * bevarer variabler ($values, $set, $params[...] osv.)
 * og undgår dobbelte semikolon.
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

    // INSERT: ->insertInto('table')->values($values)->execute()
    $contents = preg_replace(
        '/(\$[a-zA-Z0-9_]+\s*=\s*)?(?:\$this->fluent|\$fluent)->insertInto\'([a-zA-Z0-9_]+)\'\s*->values(\$[a-zA-Z0-9_]+)\s*->execute/s',
        '$1$sql = "INSERT INTO $2 (/* columns */) VALUES (/* values */)"' . "\n" .
        '$this->db->perform($sql, $3)',
        $contents
    );

    // DELETE: ->deleteFrom('table')->where('col = ?', $var)->execute()
    $contents = preg_replace(
        '/(\$[a-zA-Z0-9_]+\s*=\s*)?(?:\$this->fluent|\$fluent)->deleteFrom\'([a-zA-Z0-9_]+)\'\s*->where\'([a-zA-Z0-9_]+)\s*=\s*\?\',\s*(\$[a-zA-Z0-9_\[\'"]+)\)\s*->execute/s',
        '$1$sql = "DELETE FROM $2 WHERE $3 = :$3"' . "\n" .
        '$this->db->perform($sql, [\'$3\' => $4])',
        $contents
    );

    // UPDATE: ->update('table')->set($set)->where('col = ?', $
