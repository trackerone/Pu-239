<?php
declare(strict_types=1);

/**
 * tools/batch-43_7B-admin-autofix.php
 *
 * Conservative fixes for admin/*.php:
 *  1) Quote bare Refresh headers:
 *       header(Refresh: 2; url=...)  ->  header('Refresh: 2; url=...')
 *  2) Fix broken $db init that has comma tails (e.g. ", $site_config, $CURUSER;"):
 *       $db = $container->get(Database::class);, $site_config, $CURUSER;
 *         -> global $site_config, $CURUSER;   (only if used words are present)
 *            $db = $container->get(Database::class);
 *  3) Enforce exactly one declare(strict_types=1); on line 2; remove later duplicates.
 *  4) Trim dangling comma at very end of file (before EOF or "?>"), if any.
 *
 * Writes a report to: tools/reports/batch43_7B-summary.txt
 */

$adminDir  = __DIR__ . '/../admin';
$reportDir = __DIR__ . '/../tools/reports';
$report    = $reportDir . '/batch43_7B-summary.txt';

if (!is_dir($adminDir)) {
    fwrite(STDERR, "admin/ directory not found\n");
    exit(0);
}
if (!is_dir($reportDir)) {
    @mkdir($reportDir, 0777, true);
}

$files   = glob($adminDir . '/*.php');
if (!is_array($files)) {
    $files = [];
}

$changedFiles = [];

foreach ($files as $path) {
    $src = file_get_contents($path);
    if ($src === false) {
        continue;
    }

    $orig = $src;

    // ---- (3) Ensure opener + single declare on line 2; drop later duplicates ----
    $lines = explode("\n", $src);

    // Strip BOM on first line if present
    if (isset($lines[0])) {
        $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);
    }

    // Ensure first line is exactly "<?php"
    if (!isset($lines[0]) || trim($lines[0]) !== '<?php') {
        array_unshift($lines, '<?php');
    }

    // Ensure second line is exactly "declare(strict_types=1);"
    if (!isset($lines[1]) || trim($lines[1]) !== 'declare(strict_types=1);') {
        array_splice($lines, 1, 0, 'declare(strict_types=1);');
    }

    // Remove duplicate declare lines after line 2
    $tmp = [];
    foreach ($lines as $i => $l) {
        if ($i > 1 && preg_match('/^\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $l)) {
            continue;
        }
        $tmp[] = $l;
    }
    $src = implode("\n", $tmp);

    // ---- (1) Quote bare Refresh headers ----
    // header(Refresh: 2; url=...) -> header('Refresh: 2; url=...')
    $src = preg_replace('/header\s*\(\s*Refresh\s*:/i', "header('Refresh:", $src);
    // ensure the closing quote before the closing parenthesis of header(...)
    // pattern: header('Refresh: ....)  -> header('Refresh: ....')
    $src = preg_replace("/(header\('Refresh:[^)]*)\)(\s*;)/i", "$1'$2", $src);

    // ---- (2) Fix broken $db init with comma tails ----
    // Matches: $db = $container->get(Database::class);, $foo, $bar;
    $src = preg_replace_callback(
        '/(\$db\s*=\s*\$container->s*get\s*\(\s*Database::class\s*\)\s*;\s*),\s*([^;]+);/i',
        function (array $m): string {
            $globalsRaw = $m[2];
            $globalsRaw = trim($globalsRaw);

            $allowed = ['site_config', 'CURUSER', 'container'];
            $found   = [];

            $parts = preg_split('/\s*,\s*/', $globalsRaw);
            if (is_array($parts)) {
                foreach ($parts as $g) {
                    $g = ltrim(trim($g), '$');
                    if (in_array($g, $allowed, true)) {
                        $found[] = '$' . $g;
                    }
                }
            }

            $gline = '';
            if (!empty($found)) {
                $gline = 'global ' . implode(', ', $found) . ";\n";
            }

            return $gline . $m[1];
        },
        $src
    );

    // ---- (4) Remove a dangling comma at very end of file (before EOF or before "?>") ----
    // Example: "...\n),\n"  or  "...\n),\n?>"
    $src = preg_replace("/,\s*(\?>\s*)?$/", "$1", $src);

    if ($src !== $orig) {
        file_put_contents($path, $src);
        $changedFiles[] = basename($path);
    }
}

// ---- Write report ----
$summary  = "batch-43_7B admin autofix (safe set)\n";
$summary .= "Changed files: " . count($changedFiles) . "\n";
foreach ($changedFiles as $f) {
    $summary .= " - $f\n";
}
file_put_contents($report, $summary);
echo $summary;
