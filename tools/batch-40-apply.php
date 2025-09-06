<?php declare(strict_types=1);

$db = $container->get(Database::class);
/**
 * Batch 40 (classic, API PR) — Full repo FluentPDO sweep
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
    'Envms\\FluentPDO','->from(','->select(','->update(','->delete(',
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
        $updated = preg_replace('/^\s*use\s+Envms\\\\FluentPDO\\\\Literal;\s*$/m', '', $updated);
        $updated = preg_replace('/@throws\s+\\\\Envms\\\\FluentPDO\\\\Exception/', '@throws \\\\PDOException', $updated);
        $updated = preg_replace('/\$fluent\s*=\s*\$container->get\(Database::class\);/', '// $fluent removed — use $this->db (ExtendedPdo)', $updated);

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

echo "Batch 40 (classic) completed.\n";
