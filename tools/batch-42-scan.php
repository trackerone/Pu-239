<?php declare(strict_types=1);
/**
 * Batch 42 — FluentPDO mapping (NO code changes)
 *
 * Output (always written, even if 0 hits):
 *   tools/reports/batch42-fluentpdo-usage.ndjson   (one JSON per hit)
 *   tools/reports/batch42-summary.txt              (human summary)
 *
 * Each NDJSON row:
 *   {
 *     "file": "relative/path.php",
 *     "line": 123,
 *     "pattern": "->from(",
 *     "code": "  $fluent->from('table')->where(...);"
 *   }
 */

$root = getcwd();
$reportDir = $root . '/tools/reports';
@mkdir($reportDir, 0777, true);

$ndjsonPath = $reportDir . '/batch42-fluentpdo-usage.ndjson';
$summaryPath = $reportDir . '/batch42-summary.txt';

// ensure files exist so workflow always has something to commit
if (!file_exists($ndjsonPath)) file_put_contents($ndjsonPath, '');
if (!file_exists($summaryPath)) file_put_contents($summaryPath, '');

$ignoredDirs = [
    '/vendor/', '/node_modules/', '/.git/', '/storage/',
    '/public/uploads/', '/public/cache/', '/bootstrap/cache/',
];

// Patterns to scan (keys used in summary)
$patterns = [
    'namespace'   => '/Envms\\\\FluentPDO/',
    'from'        => '/->\s*from\s*\(/',
    'select'      => '/->\s*select\s*\(/',
    'update'      => '/->\s*update\s*\(/',
    'deleteFrom'  => '/->\s*deleteFrom\s*\(/',
    'insertInto'  => '/->\s*insertInto\s*\(/',
    'values'      => '/->\s*values\s*\(/',
    'groupBy'     => '/->\s*groupBy\s*\(/',
    'fetchAll'    => '/->\s*fetchAll\s*\(/',
    'fetchCount'  => '/->\s*fetch\s*\(\s*[\'"]count[\'"]\s*\)/',
    'fetch'       => '/->\s*fetch\s*\(\s*\)/',
    'orderBy'     => '/->\s*orderBy\s*\(/',
    'where'       => '/->\s*where\s*\(/',
];

$filesScanned = 0;
$hitsTotal = 0;
$hitsByPattern = array_fill_keys(array_keys($patterns), 0);
$hitsByFile = [];

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$nd = fopen($ndjsonPath, 'w');
if (!$nd) {
    fwrite(STDERR, "Unable to open NDJSON file for writing: $ndjsonPath\n");
    exit(1);
}

foreach ($rii as $file) {
    if (!$file->isFile()) continue;

    $path = $file->getPathname();
    $rel  = ltrim(str_replace($root, '', $path), '/');

    // skip ignored dirs
    $skip = false;
    foreach ($ignoredDirs as $dir) {
        if (strpos('/' . $rel, $dir) !== false) { $skip = true; break; }
    }
    if ($skip) continue;

    if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') continue;

    $src = @file($path, FILE_IGNORE_NEW_LINES);
    if ($src === false) continue;

    $filesScanned++;
    $fileHits = 0;

    foreach ($src as $i => $line) {
        foreach ($patterns as $name => $rx) {
            if (preg_match($rx, $line)) {
                $hitsTotal++;
                $hitsByPattern[$name]++;
                $fileHits++;

                // make a short code snippet (trim + clamp length)
                $code = trim($line);
                if (mb_strlen($code) > 200) {
                    $code = mb_substr($code, 0, 200) . ' …';
                }

                $row = [
                    'file'    => $rel,
                    'line'    => $i + 1,
                    'pattern' => $name,
                    'code'    => $code,
                ];
                fwrite($nd, json_encode($row, JSON_UNESCAPED_SLASHES) . "\n");
            }
        }
    }

    if ($fileHits > 0) {
        $hitsByFile[$rel] = $fileHits;
    }
}

fclose($nd);

// Build summary text
arsort($hitsByPattern);
arsort($hitsByFile);

$summary = [];
$summary[] = 'Batch 42 — FluentPDO mapping';
$summary[] = '================================';
$summary[] = 'Files scanned: ' . $filesScanned;
$summary[] = 'Total hits:    ' . $hitsTotal;
$summary[] = '';
$summary[] = 'Hits by pattern:';
foreach ($hitsByPattern as $p => $cnt) {
    $summary[] = sprintf('  %-11s : %d', $p, $cnt);
}
$summary[] = '';
$summary[] = 'Top files (up to 25):';
$k = 0;
foreach ($hitsByFile as $f => $cnt) {
    $summary[] = sprintf('  %4d  %s', $cnt, $f);
    if (++$k >= 25) break;
}
$summary[] = '';
$summary[] = 'NDJSON: tools/reports/batch42-fluentpdo-usage.ndjson';
$summary[] = 'Date:   ' . gmdate('c') . "\n";

file_put_contents($summaryPath, implode("\n", $summary));

echo "Batch 42 mapping completed. Files scanned: {$filesScanned}, total hits: {$hitsTotal}\n";
