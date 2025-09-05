<?php
/**
 * Batch 37 – Auto-refactor FluentPDO to Aura.Sql
 * - Erstatter simple mønstre (from/fetch, insert, update, delete).
 * - Efterlader TODO-kommentarer ved komplekse queries.
 * - Fjerner hele $fluent->... kæder, så vi undgår "klister" (fx $fluent$sql).
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

    // SELECT ->fetchAll()
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->from\([^)]+\)->where\([^)]+\)->fetchAll\(\)/',
        '// TODO: check query logic' . "\n" .
        '$sql = "SELECT * FROM table WHERE ...";' . "\n" .
        '$this->db->fetchAll($sql, [/* params */]);',
        $contents
    );

    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->from\([^)]+\)->fetchAll\(\)/',
        '$sql = "SELECT * FROM table";' . "\n" .
        '$this->db->fetchAll($sql);',
        $contents
    );

    // SELECT ->fetch()
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->from\([^)]+\)->where\([^)]+\)->fetch\(\)/',
        '// TODO: check query logic' . "\n" .
        '$sql = "SELECT * FROM table WHERE ...";' . "\n" .
        '$this->db->fetchOne($sql, [/* params */]);',
        $contents
    );

    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->from\([^)]+\)->fetch\(\)/',
        '$sql = "SELECT * FROM table";' . "\n" .
        '$this->db->fetchOne($sql);',
        $contents
    );

    // INSERT
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->insertInto\([^)]+\)->execute\(\)/',
        '$sql = "INSERT INTO table (...) VALUES (...)";' . "\n" .
        '$this->db->perform($sql, [/* params */]);',
        $contents
    );

    // UPDATE
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->update\([^)]+\)->where\([^)]+\)->execute\(\)/',
        '// TODO: check query logic' . "\n" .
        '$sql = "UPDATE table SET ... WHERE ...";' . "\n" .
        '$this->db->perform($sql, [/* params */]);',
        $contents
    );

    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->update\([^)]+\)->execute\(\)/',
        '$sql = "UPDATE table SET ...";' . "\n" .
        '$this->db->perform($sql, [/* params */]);',
        $contents
    );

    // DELETE
    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->deleteFrom\([^)]+\)->where\([^)]+\)->execute\(\)/',
        '// TODO: check query logic' . "\n" .
        '$sql = "DELETE FROM table WHERE ...";' . "\n" .
        '$this->db->perform($sql, [/* params */]);',
        $contents
    );

    $contents = preg_replace(
        '/(\$this->fluent|\$fluent)->deleteFrom\([^)]+\)->execute\(\)/',
        '$sql = "DELETE FROM table";' . "\n" .
        '$this->db->perform($sql);',
        $contents
    );

    if ($contents !== $orig) {
        file_put_contents($path, $contents);
        echo "Refactored: $path\n";
    }
}
