<?php declare(strict_types=1);

$db = $container->get(Database::class);
/**
 * Batch 41 — Replace actual FluentPDO calls with ExtendedPdo
 * 
 * Target:
 *  - $fluent->from(...)->fetchAll() → $db->fetchAll("SELECT ...")
 *  - ->fetch('count') → $db->fetchValue("SELECT COUNT(...) ...")
 *  - ->fetch() → $db->fetchRow("SELECT ...")
 *  - ->deleteFrom(...)->where(...) → $db->perform("DELETE ...")
 *  - ->update(...)->set(...)->where(...) → TODO markers (manual mapping needed)
 */

$root = getcwd();
$reportDir = $root . '/tools/reports';
@mkdir($reportDir, 0777, true);

$reportPath  = $reportDir . '/batch41-migration-report.ndjson';
$summaryPath = $reportDir . '/batch41-summary.txt';

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$filesScanned=0; $hits=0; $filesChanged=0;

$reportFp = fopen($reportPath, 'w');
if (!$reportFp) {
    fwrite(STDERR, "Cannot open report file: $reportPath\n");
    exit(1);
}

// Patterns
$patterns = [
    '->fetchAll(',
    '->fetch(',
    '->fetch(\'count\'',
    '->deleteFrom(',
    '->update(',
];

foreach ($rii as $file) {
    if (!$file->isFile()) continue;
    if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) !== 'php') continue;

    $path = $file->getPathname();
    $src = file_get_contents($path);
    if ($src === false) continue;
    $original = $src;
    $filesScanned++;

    // Simple replacements
    $src = preg_replace(
        '#->fetchAll\(\);#',
        '->fetchAll();
        $src
    );
    $src = preg_replace(
        '#->fetch\(\);#',
        '->fetch();
        $src
    );
    $src = preg_replace(
        "#->fetch\('count'\);#",
        '->fetch("count");
        $src
    );
    $src = preg_replace(
        '#->deleteFrom\(.*\)->where\(.*\)->execute\(\);#',
        '
        $src
    );
    $src = preg_replace(
        '#->update\(.*\)->set\(.*\)->where\(.*\)->execute\(\);#',
        '
        $src
    );

    if ($src !== $original) {
        file_put_contents($path, $src);
        $filesChanged++;
        fwrite($reportFp, json_encode(['file'=>$path,'changed'=>true], JSON_UNESCAPED_SLASHES)."\n");
    }
}

fclose($reportFp);

$summary = "Batch 41 migration summary\n"
         . "-------------------------\n"
         . "Files scanned: {$filesScanned}\n"
         . "Files changed: {$filesChanged}\n"
         . "Report: tools/reports/batch41-migration-report.ndjson\n"
         . "Date: " . gmdate('c') . "\n";

file_put_contents($summaryPath, $summary);

echo "Batch 41 completed: {$filesChanged} files updated.\n";
