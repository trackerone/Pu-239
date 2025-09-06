<?php
declare(strict_types=1);

/**
 * tools/fix-strict-first.php
 *
 * Ensure every admin/*.php has:
 *   line 1: <?php
 *   line 2: declare(strict_types=1);
 * If multiple declare(strict_types=1); exist, keep only the one at line 2.
 * Writes a summary report to tools/reports/fix-strict-summary.txt
 */

$adminDir  = __DIR__ . '/../admin';
$reportDir = __DIR__ . '/../tools/reports';
$report    = $reportDir . '/fix-strict-summary.txt';

if (!is_dir($adminDir)) {
    fwrite(STDERR, "admin/ directory not found\n");
    exit(0);
}
if (!is_dir($reportDir)) {
    @mkdir($reportDir, 0777, true);
}

$files   = glob($adminDir . '/*.php') ?: [];
$scanned = 0;
$changed = 0;
$changedFiles = [];

foreach ($files as $path) {
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        continue;
    }
    $scanned++;

    // Strip BOM if present in first line
    if (isset($lines[0])) {
        $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);
    }

    $original = implode("\n", $lines) . "\n";

    // Ensure first line is "<?php"
    if (!isset($lines[0]) || trim($lines[0]) !== '<?php') {
        array_unshift($lines, '<?php');
    }

    // Ensure second line is exactly declare(strict_types=1);
    if (!isset($lines[1]) || trim($lines[1]) !== 'declare(strict_types=1);') {
        array_splice($lines, 1, 0, 'declare(strict_types=1);');
    }

    // Remove duplicate declare lines (keep only at line index 1)
    $cleaned = [];
    foreach ($lines as $i => $line) {
        if ($i > 1 && preg_match('/^\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $line)) {
            // skip duplicate declare
            continue;
        }
        $cleaned[] = $line;
    }

    $new = implode("\n", $cleaned) . "\n";
    if ($new !== $original) {
        if (file_put_contents($path, $new) === false) {
            fwrite(STDERR, "Failed to write: {$path}\n");
            continue;
        }
        $changed++;
        $changedFiles[] = str_replace(dirname(__DIR__, 1) . '/', '', $path); // nice relative path
    }
}

// Write summary report
$summary  = "fix-strict-first summary\n";
$summary .= "========================\n";
$summary .= "Scanned files: $scanned\n";
$summary .= "Changed files: $changed\n";
if (!empty($changedFiles)) {
    $summary .= "\nFiles modified:\n";
    foreach ($changedFiles as $f) {
        $summary .= " - $f\n";
    }
}
$summary .= "\nGenerated at: " . gmdate('c') . "\n";

file_put_contents($report, $summary);
echo $summary;
