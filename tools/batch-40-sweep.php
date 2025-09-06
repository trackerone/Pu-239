<?php declare(strict_types=1);
/**
 * Batch 40 — Full repo FluentPDO sweep (report + optional conservative fixes)
 *
 * Behavior:
 * - Scans the entire repository (excluding vendor/node_modules/.git/storage/public/uploads/cache) for PHP files.
 * - Builds a report of FluentPDO usage patterns and writes it under tools/reports/:
 *      - tools/reports/batch40-fluentpdo-report.ndjson  (one JSON per line)
 *      - tools/reports/batch40-summary.txt
 * - If APPLY_FIXES == 'true', it also applies conservative fixes:
 *      - Remove `use Envms\FluentPDO\Literal;`
 *      - Replace `@throws \PDOException` → `@throws \PDOException`
 *      - Comment out `// $fluent removed — use $this->db (ExtendedPdo)` (leave guidance to use $this->db)
 *      - Specific well-known admin patterns (peers/agents, categories list/parents/children/delete)
 * - All messages/comments are English; codebase text untouched except the replacements.
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

$reportPath = $reportDir . '/batch40-fluentpdo-report.ndjson';
$summaryPath = $reportDir . '/batch40-summary.txt';

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

    // skip ignored dirs
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
        $row = [
            'file' => ltrim($rel, '/'),
            'hits' => $fileHits,
        ];
        fwrite($reportFp, json_encode($row, JSON_UNESCAPED_SLASHES) . "\n");

        if ($applyFixes) {
            $updated = $src;

            // Conservative replacements (safe, high-signal)

            // A) Remove FluentPDO Literal import
            $updated = preg_replace('/^\s*use\s+Envms\\\\FluentPDO\\\\Literal;\s*$/m', '', $updated);

            // B) Replace FluentPDO exception in docblocks
            $updated = preg_replace('/@throws\s+\\\\Envms\\\\FluentPDO\\\\Exception/', '@throws \\\\PDOException', $updated);

            // C) Comment out container->get(Database::class) assignment to $fluent
            $updated = preg_replace('/\$fluent\s*=\s*\$container->get\(Database::class\);/', '// $fluent removed in Batch 40 – use $this->db (ExtendedPdo)', $updated);

            // D) Admin peers/agents aggregation
            $updated = preg_replace(
                '#\$agents\s*=\s*\$fluent->from\(\'peers\'\)\s*->select\(null\)\s*->select\(\'agent\'\)\s*->select\(\'LEFT\(peer_id,\s*8\)\s+AS\s+peer_id\'\)\s*->groupBy\(\'agent\'\)\s*->groupBy\(\'peer_id\'\)\s*->fetchAll\(\);\s*#s',
                '$sql = "SELECT agent, LEFT(peer_id, 8) AS peer_id FROM peers GROUP BY agent, LEFT(peer_id, 8)";' . "\n" .
                '$agents = $this->db->fetchAll($sql);' . "\n",
                $updated
            );

            // E) Categories ordered list (iterator or fetchAll)
            $updated = preg_replace(
                '#\$cats\s*=\s*\$fluent->from\(\'categories\'\)\s*->orderBy\(\'ordered\'\)\s*;#s',
                '$sql = "SELECT * FROM categories ORDER BY ordered, id";' . "\n" .
                '$cats = $this->db->fetchAll($sql);' . "\n",
                $updated
            );
            $updated = preg_replace(
                '#\$cats\s*=\s*\$fluent->from\(\'categories\'\)\s*->orderBy\(\'ordered\'\)\s*->fetchAll\(\);\s*#s',
                '$sql = "SELECT * FROM categories ORDER BY ordered, id";' . "\n" .
                '$cats = $this->db->fetchAll($sql);' . "\n",
                $updated
            );

            // F) Categories children by parent
            $updated = preg_replace(
                '#\$children\s*=\s*\$fluent->from\(\'categories\'\)\s*->where\(\'parent_id\s*=\s*\?\',\s*\$parentId\)\s*->orderBy\(\'ordered\'\)\s*->fetchAll\(\);\s*#s',
                '$sql = "SELECT * FROM categories WHERE parent_id = :pid ORDER BY ordered, id";' . "\n" .
                '$children = $this->db->fetchAll($sql, [\'pid\' => (int) $parentId]);' . "\n",
                $updated
            );

            // G) Delete category template replacement
            $updated = preg_replace(
                '#\$fluent->deleteFrom\(\'categories\'\)\s*->where\(\'id\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->execute\(\);\s*#s',
                '$this->db->perform("DELETE FROM categories WHERE id = :id", ["id" => (int) $params["id"]]);' . "\n",
                $updated
            );

            if ($updated !== $src) {
                file_put_contents($path, $updated);
            }
        }
    }
}

fclose($reportFp);

$summary = "Batch 40 FluentPDO sweep summary\n"
         . "--------------------------------\n"
         . "Files scanned: {$filesScanned}\n"
         . "Total FluentPDO-related hits: {$hits}\n"
         . "Report: tools/reports/batch40-fluentpdo-report.ndjson\n"
         . "Date: " . gmdate('c') . "\n";

file_put_contents($summaryPath, $summary);

// Ensure reports are added to git by the workflow
echo "Batch 40 sweep completed.\n";
