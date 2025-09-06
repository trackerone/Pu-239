<?php
declare(strict_types=1);

/**
 * tools/fix-strict-first.php
 *
 * Force all admin/*.php files to have:
 *   line 1: <?php
 *   line 2: declare(strict_types=1);
 * Remove any duplicate declare lines below line 2.
 */

$dir = __DIR__ . '/../admin';
$reportDir = __DIR__ . '/../tools/reports';
$reportFile = $reportDir . '/fix-strict-summary.txt';

if (!is_dir($dir)) {
    fwrite(STDERR, "admin/ directory not found\n");
    exit(0);
}
if (!is_dir($reportDir)) {
    mkdir($reportDir, 0777, true);
}

$files = glob($dir . '/*.php') ?: [];
$scanned = 0;
$changed = 0;
$changedFiles = [];

foreach ($files as $path) {
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        continue;
    }
    $scanned++;

    // Strip BOM
    if (isset($lines[0])) {
        $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);
    }

    $original = implode("\n", $lines) . "\n";

    // Ensure first line
    if (!isset($lines[0]) || trim($lines[0]) !== '<?php') {
        array_unshift($lines, '<?php');
    }

    // Ensure second line
    if (!isset($lines[1]) || trim($lines[1]) !== 'declare(strict_types=1);') {
        array_splice($lines, 1, 0, 'declare(strict_types=1);');
    }

    // Remove duplicate declare lines after line 1
    $cleaned = [];
    foreach ($lines as $i => $line) {
        if ($i > 1 && trim($line) === 'declare(strict_types=1);') {
            continue; // skip duplicates
        }
        $cleaned[] = $line;
    }

    $new = implode("\n", $cleaned) . "\n";
    if ($new !== $original) {
        file_put_contents($path, $new);
        $changed++;
        $changedFiles[] = basename($path);
    }
}

// Write report
$summary  = "fix-strict-first summary\n";
$summary .= "========================\n";
$summary .= "Scanned: $scanned\n";
$summary .= "Changed: $changed\n";
if ($changedFiles) {
    $summary .= "\nFiles modified:\n";
    foreach ($changedFiles as $f) {
        $summary .= " - $f\n";
    }
}
$summary .= "\nGenerated: " . gmdate('c') . "\n";

file_put_contents($reportFile, $summary);
echo $summary;
