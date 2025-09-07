<?php
declare(strict_types=1);

/**
 * tools/report-parse-errors.php
 *
 * Scan admin/*.php with `php -l` and write a consolidated report:
 *   tools/reports/batch43_7C-parse-errors.txt
 *
 * This script never exits non-zero: it's report-only.
 */

$adminDir  = __DIR__ . '/../admin';
$reportDir = __DIR__ . '/../tools/reports';
$report    = $reportDir . '/batch43_7C-parse-errors.txt';

if (!is_dir($adminDir)) {
    fwrite(STDERR, "admin/ directory not found\n");
    exit(0);
}
if (!is_dir($reportDir)) {
    @mkdir($reportDir, 0777, true);
}

$files = glob($adminDir . '/*.php') ?: [];
$errors = [];

foreach ($files as $path) {
    // Use PHP's CLI linter for authoritative parse errors
    $cmd = escapeshellcmd(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $out = shell_exec($cmd);
    if (!is_string($out)) {
        $out = '';
    }
    // Normal success line: "No syntax errors detected in <file>"
    if (strpos($out, 'No syntax errors detected') === false) {
        // Keep only the first line for a concise summary
        $first = strtok($out, "\n");
        $errors[] = [$path, $first ?: trim($out)];
    }
}

$ts = gmdate('c');
$lines = [];
$lines[] = "Batch 43.7C — admin/*.php parse error report";
$lines[] = "Generated: {$ts}";
$lines[] = str_repeat('=', 64);
$lines[] = "Scanned files: " . count($files);
$lines[] = "Files with errors: " . count($errors);
$lines[] = '';

if ($errors) {
    foreach ($errors as [$file, $line]) {
        // Example error format from php -l:
        // "PHP Parse error:  syntax error, unexpected ... in admin/foo.php on line 123"
        $lines[] = $file;
        $lines[] = '  ' . $line;
        $lines[] = '';
    }
} else {
    $lines[] = 'All clear: no parse errors detected.';
}

$body = implode("\n", $lines) . "\n";
file_put_contents($report, $body);

echo $body;
