<?php
$db = $container->get(Database::class);

/**
 * Scan project files for FluentPDO references.
 * Outputs findings to stdout (can be saved to artifact in CI).
 *
 * Soft guard: always exit 0.
 */

$root = dirname(__DIR__);
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$patterns = [
    'FluentPDO',          // class instantiation
    '->from(',            // common query builder methods
    '->insertInto(', 
    '->update(', 
    '->deleteFrom(',
];

$results = [];
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    // skip vendor and node_modules
    if (strpos($path, '/vendor/') !== false || strpos($path, '/node_modules/') !== false) continue;
    if (substr($path, -4) !== '.php') continue;

    $lines = file($path);
    foreach ($lines as $num => $line) {
        foreach ($patterns as $p) {
            if (strpos($line, $p) !== false) {
                $results[] = sprintf("%s:%d: %s", $path, $num+1, trim($line));
            }
        }
    }
}

echo "== FluentPDO reference scan ==\n\n";
if ($results) {
    foreach ($results as $r) echo $r . "\n";
    echo "\nTotal matches: " . count($results) . "\n";
} else {
    echo "No FluentPDO references found.\n";
}

exit(0);
