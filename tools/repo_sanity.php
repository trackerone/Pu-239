<?php
declare(strict_types=1);

/**
 * tools/repo_sanity.php
 *
 * Purpose:
 *  1) Bootstrap the DI container and verify DB connectivity.
 *  2) Repo-wide static scan for risky patterns (debug calls, unsafe exec, etc.).
 *  3) Write NDJSON + summary reports under tools/reports/.
 *
 * Output:
 *  - tools/reports/repo_sanity_report.ndjson   (one JSON per finding)
 *  - tools/reports/repo_sanity_summary.txt     (human-readable summary)
 *
 * Exit codes:
 *  - 0 = OK (no critical findings)
 *  - 1 = Critical findings detected OR DB sanity failed
 */

require_once __DIR__ . '/../include/runtime_safe.php';

use Pu239\Database;

$root       = dirname(__DIR__);
$reportDir  = $root . '/tools/reports';
@mkdir($reportDir, 0777, true);
$ndjsonPath = $reportDir . '/repo_sanity_report.ndjson';
$sumPath    = $reportDir . '/repo_sanity_summary.txt';

// --- 1) Bootstrap + DB sanity ------------------------------------------------
global $container;
/** @var \DI\Container|null $container */
if (!isset($container)) {
    file_putContentsSafe($sumPath, "Container not initialized — check runtime_safe.php.\n");
    fwrite(STDERR, "Container not initialized — check runtime_safe.php include path.\n");
    exit(1);
}

try {
    /** @var Database $db */
    $db = $container->get(Database::class);
} catch (Throwable $e) {
    file_putContentsSafe($sumPath, "Failed to get Database from container: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Failed to get Database from container: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Repo sanity: DB connectivity test...\n";
try {
    $val = $db->fetchValue('SELECT 1');
    echo "DB responded with: {$val}\n";
} catch (Throwable $e) {
    file_putContentsSafe($sumPath, "DB sanity failed: " . $e->getMessage() . "\n");
    fwrite(STDERR, "DB sanity failed: " . $e->getMessage() . "\n");
    exit(1);
}

// Optional: light schema presence check
try {
    $ok = $db->fetchValue("SELECT COUNT(*) FROM information_schema.tables");
    echo "information_schema.tables visible: {$ok}\n";
} catch (Throwable $e) {
    echo "Skipping schema check: " . $e->getMessage() . "\n";
}

// --- 2) Static repo scan -----------------------------------------------------
$ignored = [
    '/vendor/', '/node_modules/', '/.git/', '/storage/',
    '/public/uploads/', '/public/cache/', '/bootstrap/cache/',
    '/.idea/', '/.vscode/', '/.github/', '/.husky/',
];

$patterns = [
    // name => [regex, severity, description]
    'var_dump'      => ['/\bvar_dump\s*\(/i',                           'warn',  'Debug output'],
    'print_r'       => ['/\bprint_r\s*\(\s*[^,)]*\s*\)\s*;?/i',         'warn',  'Debug output (no second param true)'],
    'dd'            => ['/\bdd\s*\(/i',                                 'warn',  'Debug die/dump'],
    'dump'          => ['/\bdump\s*\(/i',                               'warn',  'Debug dump'],
    'die'           => ['/\bdie\s*\(/i',                                'crit',  'Hard termination'],
    'exit'          => ['/\bexit\s*\(/i',                               'crit',  'Hard termination'],
    'eval'          => ['/\beval\s*\(/i',                               'crit',  'Dynamic code execution'],
    'shell_exec'    => ['/\bshell_exec\s*\(/i',                         'crit',  'Exec external command'],
    'exec'          => ['/\bexec\s*\(/i',                               'crit',  'Exec external command'],
    'passthru'      => ['/\bpassthru\s*\(/i',                           'crit',  'Exec external command'],
    'system'        => ['/\bsystem\s*\(/i',                             'crit',  'Exec external command'],
    'backticks'     => ['/(^|[^\w`])`[^`]+`/m',                         'crit',  'Backtick execution'],
    // app-specific preference: prefer app_halt over bare die/exit
    'bare_die_exit' => ['/\b(die|exit)\s*(;|\(\s*\))\s*$/m',            'crit',  'Bare die/exit (prefer app_halt)'],
];

$allowByFile = [
    // relative path => list of allowed pattern names (if some files intentionally use exit/exec etc.)
    // 'tools/some_script.php' => ['exit', 'system'],
];

$findings = [];
$counts   = ['warn' => 0, 'crit' => 0, 'total' => 0];
$filesScanned = 0;

/** Scan */
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$nd = fopen($ndjsonPath, 'w');
if (!$nd) {
    fwrite(STDERR, "Unable to open NDJSON report for writing: {$ndjsonPath}\n");
    exit(1);
}

foreach ($it as $fileInfo) {
    if (!$fileInfo->isFile()) continue;

    $path = $fileInfo->getPathname();
    $rel  = ltrim(str_replace($root, '', $path), '/');

    // Skip ignored dirs
    $skip = false;
    foreach ($ignored as $dir) {
        if (strpos('/' . $rel, $dir) !== false) {
            $skip = true; break;
        }
    }
    if ($skip) continue;

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['php', 'phtml', 'inc'], true)) continue;

    $contents = @file($path, FILE_IGNORE_NEW_LINES);
    if ($contents === false) continue;

    $filesScanned++;

    foreach ($contents as $ln => $line) {
        $lineCheck = rtrim($line);

        // Heuristic: allow harmless "exit" in CLI shebang lines? (not relevant in PHP, but left as example)
        foreach ($patterns as $name => [$rx, $severity, $desc]) {
            if (!preg_match($rx, $lineCheck)) {
                continue;
            }

            // Allowlist per file override
            if (isset($allowByFile[$rel]) && in_array($name, $allowByFile[$rel], true)) {
                continue;
            }

            $snippet = trim($lineCheck);
            if (mb_strlen($snippet) > 240) {
                $snippet = mb_substr($snippet, 0, 240) . ' …';
            }

            $row = [
                'file'      => $rel,
                'line'      => $ln + 1,
                'pattern'   => $name,
                'severity'  => $severity,
                'message'   => $desc,
                'code'      => $snippet,
            ];
            $findings[] = $row;
            fwrite($nd, json_encode($row, JSON_UNESCAPED_SLASHES) . "\n");

            $counts[$severity]  = ($counts[$severity] ?? 0) + 1;
            $counts['total']    = ($counts['total'] ?? 0) + 1;
        }
    }
}
fclose($nd);

// --- 3) Summary + exit code --------------------------------------------------
$summary = [];
$summary[] = 'Repo Sanity — summary';
$summary[] = '=====================';
$summary[] = 'Files scanned: ' . $filesScanned;
$summary[] = 'Findings total: ' . ($counts['total'] ?? 0);
$summary[] = '  - warn: ' . ($counts['warn'] ?? 0);
$summary[] = '  - crit: ' . ($counts['crit'] ?? 0);
$summary[] = '';
$summary[] = 'Report: tools/reports/repo_sanity_report.ndjson';
$summary[] = 'Date:   ' . gmdate('c');
$summary[] = '';

if (!empty($findings)) {
    $summary[] = 'Top (up to 25) findings:';
    $i = 0;
    foreach ($findings as $f) {
        $summary[] = sprintf(
            '  [%s] %s:%d — %s',
            strtoupper($f['severity']),
            $f['file'],
            $f['line'],
            $f['pattern']
        );
        if (++$i >= 25) break;
    }
    $summary[] = '';
}

file_putContentsSafe($sumPath, implode("\n", $summary) . "\n");

$exit = ($counts['crit'] ?? 0) > 0 ? 1 : 0;
if ($exit === 1) {
    fwrite(STDERR, "Critical findings detected. See {$ndjsonPath}\n");
}
echo implode("\n", $summary) . "\n";
exit($exit);

/** ------------------------------------------------------------------------ */
function file_putContentsSafe(string $path, string $content): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @file_put_contents($path, $content);
}
