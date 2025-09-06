<?php
declare(strict_types=1);

/**
 * tools/batch-43_7B-admin-autofix.php
 *
 * Conservative fixes:
 *  1) header(Refresh: …)  -> header('Refresh: …')
 *  2) $db init with comma tails -> split into proper $db + optional global line
 *  3) remove trailing ",)" or "),” at end-of-file or before "?>"
 *  4) keep only first declare(strict_types=1); on line 2 if duplicates exist
 * Writes report to tools/reports/batch43_7B-summary.txt
 */

$dir       = __DIR__ . '/../admin';
$reportDir = __DIR__ . '/../tools/reports';
$report    = $reportDir . '/batch43_7B-summary.txt';

@mkdir($reportDir, 0777, true);

$files   = glob($dir . '/*.php') ?: [];
$changed = [];
foreach ($files as $path) {
    $src = file_get_contents($path);
    if ($src === false) continue;
    $orig = $src;

    // 4) Remove later duplicate declare lines (keep only first occurrence exactly at line 2)
    $lines = explode("\n", $src);
    if (isset($lines[0])) $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]); // strip BOM
    // ensure first two lines are opener + declare
    if (!isset($lines[0]) || trim($lines[0]) !== '<?php') array_unshift($lines, '<?php');
    if (!isset($lines[1]) || trim($lines[1]) !== 'declare(strict_types=1);') array_splice($lines, 1, 0, 'declare(strict_types=1);');
    // drop any duplicate declare after line 2
    $tmp = [];
    foreach ($lines as $i => $l) {
        if ($i > 1 && preg_match('/^\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $l)) continue;
        $tmp[] = $l;
    }
    $src = implode("\n", $tmp);

    // 1) Quote bare Refresh header
    // header(Refresh: 2; url=...
    $src = preg_replace('/header\s*\(\s*Refresh\s*:/i', "header('Refresh:", $src);
    // ensure closing quote before );
    $src = preg_replace("/header\('Refresh:([^)]*)\)(\s*;)/i", "header('Refresh:$1')$2", $src);

    // 2) Fix broken $db init dotted with globals: "$db = ...);, $X[, $Y];"
    // common bad forms:
    //   $db = $container->get(Database::class);, $site_config;
    //   $db = $container->get(Database::class);, $site_config, $CURUSER;
    $src = preg_replace_callback(
        '/(\$db\s*=\s*\$container->get\s*\(\s*Database::class\s*\)\s*;\s*),\s*([^;]+);/i',
        function ($m) {
            $globals = trim($m[2]);
            // keep only known globals
            $keep = [];
            foreach (preg_split('/\s*,\s*/', $globals) as $g) {
                $g = trim($g, " \t\n\r\0\x0B$");
                if ($g === 'site_config' || $g === 'CURUSER' || $g === 'container') $keep[] = '$' . $g;
            }
            $gline = '';
            if (!empty($keep)) {
                // build: global $a, $b;
                $gline = "global " . implode(', ', $keep) . ";\n";
            }
            return $gline . $m[1];
        },
        $src
    );

    // 3) Remove dangling comma before end-of-file or before "?>"
    // e.g., "func(...),\n?>", "func(...),\n"
    $src = preg_replace("/,\s*(\?>\s*)?$/", "$1", $src);

    if ($src !== $orig) {
        file_put_contents($path, $src);
        $changed[] = basename($path);
    }
}

$summary  = "batch-43_7B admin autofix (safe set)\n";
$summary .= "Changed files: " . count($changed) . "\n";
foreach ($changed as $f) $summary .= " - $f\n";
file_put_contents($report, $summary);
echo $summary;
