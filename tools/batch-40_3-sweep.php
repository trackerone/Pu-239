<?php declare(strict_types=1);

$db = $container->get(Database::class);
/**
 * Batch 40.3 — Full repo FluentPDO sweep (ALWAYS writes reports)
 *
 * - Scans the entire repo (excludes vendor/node_modules/.git/storage/uploads/cache/bootstrap/cache).
 * - Writes:
 *     tools/reports/batch40_3-fluentpdo-report.ndjson  (one JSON object per file with hits)
 *     tools/reports/batch40_3-summary.txt              (human summary)
 * - If APPLY_FIXES=true, performs conservative fixes on safe patterns (same set as 40 v1):
 *     * remove Literal import
 *     * replace FluentPDO exception in docblocks
 *     * comment out $fluent = $container->get(Database::class)
 *     * simple admin peers/categories patterns
 * - Always succeeds and always writes the report files (even with zero hits).
 */

$root = getcwd();
$reportDir = $root . '/tools/reports';
@mkdir($reportDir, 0777, true);

$ignoredDirs = [
    '/vendor/',
    '/node_modules/',
    '/.git/',
    '/storage/',
    '/public/uploads/',
    '/public/cache/',
    '/bootstrap/cache/',
];

$applyFixes = getenv('APPLY_FIXES') === 'true';

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$hits = 0;
$filesScanned = 0;
$filesWithHits = 0;

$reportPath = $reportDir . '/batch40_3-fluentpdo-report.ndjson';
$summaryPath = $reportDir . '/batch40_3-summary.txt';

$reportFp = fopen($reportPath, 'w');
if (!$reportFp) {
    fwrite(STDERR, "Unable to open report file for writing: $reportPath\n");
    exit(1);
}

$patternList = [
    'Envms\\\FluentPDO',
    '->from(',
    '->select(',
    '->update(',
    '->delete(',
    '->insertInto(',
    '->values(',
    '->fetchAll(',
    '->fetch(',
    "->fetch('count'",
    '->groupBy(',
];

foreach ($rii as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    $rel = str_replace($root, '', $path);

    // Skip ignored dirs
    $skip = false;
    foreach ($ignoredDirs as $id) {
        if (strpos($rel, $id) !== false) { $skip = true; break; }
    }
    if ($skip) continue;

    if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') continue;

    $filesScanned++;
    $src = file_get_contents($path);
    if ($src === false) continue;

    $fileHits = [];
    foreach ($patternList as $p) {
        $cnt = substr_count($src, $p);
        if ($cnt > 0) {
            $fileHits[$p] = $cnt;
            $hits += $cnt;
        }
    }

    if (!empty($fileHits)) {
        $filesWithHits++;
        $row = [
            'file' => ltrim($rel, '/'),
            'hits' => $fileHits,
        ];
        fwrite($reportFp, json_encode($row, JSON_UNESCAPED_SLASHES) . "\n");

        if ($applyFixes) {
            $updated = $src;

            // A) Remove FluentPDO Literal import
            $updated = preg_replace('/^\s*use\s+Envms\\\\FluentPDO\\\\Literal;\s*$/m', '', $updated);

            // B) Replace FluentPDO exception in docblocks
            $updated = preg_replace('/@throws\s+\\\\Envms\\\\FluentPDO\\\\Exception/', '@throws \\\\PDOException', $updated);

            // C) Comment out container->get(Database::class) assignment to $fluent
            $updated = preg_replace('/\$fluent\s*=\s*\$container->get\(Database::class\);/', '// $fluent removed in Batch 40.3 – use $this->db (ExtendedPdo)', $updated);

            // D) Admin peers → agents aggregation
            $updated = preg_replace(
                '#\$agents\s*=\s*\$fluent->from\(\'peers\'\)\s*->select\(null\)\s*->select\(\'agent\'\)\s*->select\(\'LEFT\(peer_id,\s*8\)\s+AS\s+peer_id\'\)\s*->groupBy\(\'agent\'\)\s*->groupBy\(\'peer_id\'\)\s*->fetchAll\(\);\s*#s',
                '$sql = "SELECT agent, LEFT(peer_id, 8) AS peer_id FROM peers GROUP BY agent, LEFT(peer_id, 8)";' . "\n" .
                '$agents = $db->fetchAll($sql);' . "\n",
                $updated
            );

            // E) Categories ordered list (iterator or fetchAll)
            $updated = preg_replace(
                '#\$cats\s*=\s*\$fluent->from\(\'categories\'\)\s*->orderBy\(\'ordered\'\)\s*;#s',
                '$sql = "SELECT * FROM categories ORDER BY ordered, id";' . "\n" .
                '$cats = $db->fetchAll($sql);' . "\n",
                $updated
            );
            $updated = preg_replace(
                '#\$cats\s*=\s*\$fluent->from\(\'categories\'\)\s*->orderBy\(\'ordered\'\)\s*->fetchAll\(\);\s*#s',
                '$sql = "SELECT * FROM categories ORDER BY ordered, id";' . "\n" .
                '$cats = $db->fetchAll($sql);' . "\n",
                $updated
            );

            // F) Categories children by parent
            $updated = preg_replace(
                '#\$children\s*=\s*\$fluent->from\(\'categories\'\)\s*->where\(\'parent_id\s*=\s*\?\',\s*\$parentId\)\s*->orderBy\(\'ordered\'\)\s*->fetchAll\(\);\s*#s',
                '$sql = "SELECT * FROM categories WHERE parent_id = :pid ORDER BY ordered, id";' . "\n" .
                '$children = $db->fetchAll($sql, [\'pid\' => (int) $parentId]);' . "\n",
                $updated
            );

            // G) Delete category by id
            $updated = preg_replace(
                '#\$fluent->deleteFrom\(\'categories\'\)\s*->where\(\'id\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->execute\(\);\s*#s',
                '$db->perform("DELETE FROM categories WHERE id = :id", ["id" => (int) $params["id"]]);' . "\n",
                $updated
            );

            if ($updated !== $src) {
                file_put_contents($path, $updated);
            }
        }
    }
}

fclose($reportFp);

$summary = "Batch 40.3 FluentPDO sweep summary\n"
         . "---------------------------------\n"
         . "Files scanned: {$filesScanned}\n"
         . "Files with FluentPDO hits: {$filesWithHits}\n"
         . "Total hits (approx): {$hits}\n"
         . "Report: tools/reports/batch40_3-fluentpdo-report.ndjson\n"
         . "Date: " . gmdate('c') . "\n";

file_put_contents($summaryPath, $summary);

echo "Batch 40.3 sweep completed.\n";
