<?php declare(strict_types=1);
/**
 * Batch 43.5 — Admin audit (reports only)
 *
 * Scope: admin/*.php
 * Detects (no code changes):
 *  - this_db     : occurrences of $this->db->
 *  - this_cache  : occurrences of $this->cache->
 *  - missing_db  : missing `$db = $container->get(Database::class);`
 *  - bad_strict  : declare(strict_types=1) not first statement (or missing)
 *  - broken_run  : broken `$db->run(');` fragments
 *  - mysqli_mix  : usage of mysqli_* or sql_query alongside $db
 *  - bare_exit   : bare die()/exit() (suggest app_halt)
 *  - todo41      : TODO(batch41) leftovers
 *  - bad_tail    : a bad ", $site_config;" tail after $db init
 *  - bad_hint    : hint comment still refers to $this->db
 *
 * Writes:
 *  - tools/reports/batch43_5-admin-audit.ndjson
 *  - tools/reports/batch43_5-summary.txt
 */

$root = getcwd();
$admin = $root . '/admin';
$reportDir = $root . '/tools/reports';
@mkdir($reportDir, 0777, true);
$ndjson = $reportDir . '/batch43_5-admin-audit.ndjson';
$summary = $reportDir . '/batch43_5-summary.txt';

if (!is_dir($admin)) {
    file_put_contents($summary, "No admin/ directory.\n");
    echo "No admin/ directory.\n";
    exit(0);
}

$fp = fopen($ndjson, 'w');
if (!$fp) {
    fwrite(STDERR, "Unable to open report file: $ndjson\n");
    exit(1);
}

$files = new DirectoryIterator($admin);
$filesScanned = 0;
$findings = [];
$counts = [
    'this_db'    => 0,
    'this_cache' => 0,
    'missing_db' => 0,
    'bad_strict' => 0,
    'broken_run' => 0,
    'mysqli_mix' => 0,
    'bare_exit'  => 0,
    'todo41'     => 0,
    'bad_tail'   => 0,
    'bad_hint'   => 0,
];

foreach ($files as $f) {
    if ($f->isDot() || !$f->isFile()) continue;
    if (pathinfo($f->getFilename(), PATHINFO_EXTENSION) !== 'php') continue;

    $path = $f->getPathname();
    $rel  = 'admin/' . $f->getFilename();
    $srcLines = @file($path, FILE_IGNORE_NEW_LINES);
    if ($srcLines === false) continue;

    $filesScanned++;

    // Build a single string for some checks
    $src = implode("\n", $srcLines);

    // Check strict_types is the very first statement
    $badStrict = false;
    if (!preg_match('/^<\?php\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i', $src)) {
        // either missing or not first (we treat both as bad)
        $badStrict = true;
        $counts['bad_strict']++;
        writeFinding($fp, $rel, 1, 'bad_strict', 'declare(strict_types=1) missing or not first statement');
    }

    // Check for $db init
    $hasDbInit = preg_match('/\$db\s*=\s*\$container->get\(Database::class\);/m', $src) === 1;
    if (!$hasDbInit) {
        $counts['missing_db']++;
        writeFinding($fp, $rel, 1, 'missing_db', 'Missing $db = $container->get(Database::class);');
    }

    // Broken $db->run(');
    foreach ($srcLines as $ln => $line) {
        if (preg_match('/\$db->run\(\s*\'\s*\)\s*;/', $line)) {
            $counts['broken_run']++;
            writeFinding($fp, $rel, $ln + 1, 'broken_run', "Broken \$db->run('); fragment");
        }
    }

    // $this->db / $this->cache
    foreach ($srcLines as $ln => $line) {
        if (strpos($line, '$this->db->') !== false) {
            $counts['this_db']++;
            writeFinding($fp, $rel, $ln + 1, 'this_db', 'Use of $this->db in admin script');
        }
        if (strpos($line, '$this->cache->') !== false) {
            $counts['this_cache']++;
            writeFinding($fp, $rel, $ln + 1, 'this_cache', 'Use of $this->cache in admin script');
        }
    }

    // mysqli/sql_query mix
    $hasMysqli = false;
    foreach ($srcLines as $ln => $line) {
        if (preg_match('/\b(mysqli_|sql_query\s*\()/i', $line)) {
            $hasMysqli = true;
            $counts['mysqli_mix']++;
            writeFinding($fp, $rel, $ln + 1, 'mysqli_mix', 'mysqli/sql_query present (prefer $db methods)');
        }
    }

    // bare die/exit (not inside comment)
    foreach ($srcLines as $ln => $line) {
        if (preg_match('/\b(die|exit)\s*(\(|;)/', $line) && stripos($line, 'app_halt') === false) {
            $counts['bare_exit']++;
            writeFinding($fp, $rel, $ln + 1, 'bare_exit', 'Bare die/exit found (prefer app_halt)');
        }
    }

    // TODO(batch41)
    foreach ($srcLines as $ln => $line) {
        if (strpos($line, 'TODO(batch41)') !== false) {
            $counts['todo41']++;
            writeFinding($fp, $rel, $ln + 1, 'todo41', 'Leftover TODO(batch41)');
        }
    }

    // bad tail after db init: ", $site_config;"
    foreach ($srcLines as $ln => $line) {
        if (preg_match('/\$db\s*=\s*\$container->get\(Database::class\);\s*,\s*\$site_config\s*;/', $line)) {
            $counts['bad_tail']++;
            writeFinding($fp, $rel, $ln + 1, 'bad_tail', 'Bad tail after $db init: ", $site_config;"');
        }
    }

    // wrong hint comment ($this->db)
    foreach ($srcLines as $ln => $line) {
        if (strpos($line, '// $fluent removed — use $this->db') !== false) {
            $counts['bad_hint']++;
            writeFinding($fp, $rel, $ln + 1, 'bad_hint', 'Fluent hint still points to $this->db (should be $db)');
        }
    }
}

fclose($fp);

// Summary
$lines = [];
$lines[] = 'Batch 43.5 — Admin audit (reports only)';
$lines[] = '======================================';
$lines[] = 'Files scanned: ' . $filesScanned;
$lines[] = '';
$lines[] = 'Findings (totals):';
foreach ($counts as $k => $v) {
    $lines[] = sprintf('  %-11s : %d', $k, $v);
}
$lines[] = '';
$lines[] = 'Report: tools/reports/batch43_5-admin-audit.ndjson';
$lines[] = 'Date:   ' . gmdate('c') . "\n";
file_put_contents($summary, implode("\n", $lines));

echo implode("\n", $lines);

/** helper */
function writeFinding($fp, string $file, int $line, string $code, string $msg): void {
    fwrite($fp, json_encode([
        'file' => $file,
        'line' => $line,
        'issue' => $code,
        'message' => $msg,
    ], JSON_UNESCAPED_SLASHES) . "\n");
}
