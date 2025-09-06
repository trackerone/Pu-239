<?php declare(strict_types=1);
/**
 * Batch 40 (classic) — Full repo FluentPDO sweep
 * - Always writes reports so there's something to commit.
 * - Conservative fixes only (imports/docblocks/$fluent hints + a few admin patterns).
 */

$root = getcwd();
$reportDir = $root . '/tools/reports';
@mkdir($reportDir, 0777, true);

$reportPath  = $reportDir . '/batch40-classic-report.ndjson';
$summaryPath = $reportDir . '/batch40-classic-summary.txt';

$ignored = [
    '/vendor/','/node_modules/','/.git/','/storage/',
    '/public/uploads/','/public/cache/','/bootstrap/cache/'
];

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$filesScanned=0; $hits=0; $filesWithHits=0;

$reportFp = fopen($reportPath, 'w');
if (!$reportFp) {
    fwrite(STDERR, "Cannot open report file: $reportPath\n");
    exit(1);
}

$needles = [
    'Envms\\\FluentPDO','->from(','->select(','->update(','->delete(',
    '->insertInto(','->values(','->fetchAll(','->fetch(','->groupBy(',
];

foreach ($rii as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    $rel  = str_replace($root, '', $path);
    foreach ($ignored as $sk) { if (strpos($rel,$sk)!==false) { continue 2; } }
    if (pathinfo($path, PATHINFO_EXTENSION)!=='php') continue;

    $filesScanned++;
    $src = file_get_contents($path);
    if ($src===false) continue;

    $fileHits = [];
    foreach ($needles as $n) {
        $c = substr_count($src, $n);
        if ($c>0) { $fileHits[$n]=$c; $hits+=$c; }
    }

    if (!empty($fileHits)) {
        $filesWithHits++;
        fwrite($reportFp, json_encode(['file'=>ltrim($rel,'/'),'hits'=>$fileHits], JSON_UNESCAPED_SLASHES)."\n");

        // Conservative fixes
        $updated = $src;

        // A) Remove Literal import
        $updated = preg_replace('/^\s*use\s+Envms\\\\FluentPDO\\\\Literal;\s*$/m', '', $updated);

        // B) Docblock exception
        $updated = preg_replace('/@throws\s+\\\\Envms\\\\FluentPDO\\\\Exception/', '@throws \\\\PDOException', $updated);

        // C) $fluent from container
        $updated = preg_replace('/\$fluent\s*=\s*\$container->get\(Database::class\);/', '// $fluent removed — use $this->db (ExtendedPdo)', $updated);

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

        // G) Delete category by id
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

fclose($reportFp);

$summary = "Batch 40 (classic) FluentPDO sweep summary\n"
        .  "--------------------------------------------\n"
        .  "Files scanned: {$filesScanned}\n"
        .  "Files with FluentPDO hits: {$filesWithHits}\n"
        .  "Total hits (approx): {$hits}\n"
        .  "Report: tools/reports/batch40-classic-report.ndjson\n"
        .  "Date: " . gmdate('c') . "\n";
file_put_contents($summaryPath, $summary);

echo "Batch 40 (classic) completed.\\n";
