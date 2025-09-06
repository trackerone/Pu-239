<?php
declare(strict_types=1);

/**
 * tools/static_guard.php
 *
 * Purpose:
 *  - Run static analysis / consistency checks for the Pu-239 codebase.
 *  - Can be extended with custom guards (e.g., scanning for banned functions).
 */

require_once __DIR__ . '/../include/runtime_safe.php';

use Pu239\Database;

global $container;
/** @var \DI\Container|null $container */
if (!isset($container)) {
    fwrite(STDERR, "Container not initialized — check include/runtime_safe.php path.\n");
    exit(1);
}

/** @var Database $db */
$db = $container->get(Database::class);

// -----------------------------------------------------------------------------
// Example static checks
// -----------------------------------------------------------------------------

echo "Static Guard: starting checks...\n";

// 1) Verify DB connectivity
try {
    $val = $db->fetchValue('SELECT 1');
    echo "DB connectivity OK (SELECT 1 returned {$val})\n";
} catch (Throwable $e) {
    fwrite(STDERR, "DB connectivity check failed: " . $e->getMessage() . "\n");
    exit(1);
}

// 2) Simple repo scan for forbidden patterns
$root = dirname(__DIR__);
$forbidden = [
    'var_dump(',
    'print_r(',
    'dd(',
    'dump(',
    'eval(',
    'shell_exec(',
    'exec(',
    'passthru(',
    'system(',
    '`', // backticks
];
$hits = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
    if (!in_array($ext, ['php', 'phtml', 'inc'], true)) {
        continue;
    }
    $rel = substr($file->getPathname(), strlen($root) + 1);
    $lines = @file($file->getPathname(), FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        continue;
    }
    foreach ($lines as $ln => $line) {
        foreach ($forbidden as $pat) {
            if (strpos($line, $pat) !== false) {
                $hits[] = [
                    'file' => $rel,
                    'line' => $ln + 1,
                    'pattern' => $pat,
                    'code' => trim($line),
                ];
            }
        }
    }
}

if ($hits) {
    echo "Static Guard: forbidden patterns found!\n";
    foreach ($hits as $h) {
        echo sprintf(
            "  %s:%d contains %s → %s\n",
            $h['file'],
            $h['line'],
            $h['pattern'],
            $h['code']
        );
    }
    exit(1);
}

echo "Static Guard: all checks passed ✅\n";
