<?php
declare(strict_types=1);

/**
 * tools/batch-43_7B-admin-autofix.php
 *
 * Conservative fixes for admin/*.php:
 *  1) Ensure opener + exactly one "declare(strict_types=1);" on line 2 (remove later duplicates).
 *  2) Quote bare Refresh headers:
 *       header(Refresh: 2; url=...)  ->  header('Refresh: 2; url=...')
 *  3) Fix broken $db init that has comma tails (e.g. ", $site_config, $CURUSER;"):
 *       $db = $container->get(Database::class);, $site_config, $CURUSER;
 *         -> global $site_config, $CURUSER;
 *            $db = $container->get(Database::class);
 *
 * Writes report to tools/reports/batch43_7B-summary.txt
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

$files = glob($adminDir . '/*.php');
if (!is_array($files)) {
    $files = [];
}

$changed = [];

foreach ($files as $path) {
    $src = file_get_contents($path);
    if ($src === false) {
        continue;
    }
    $orig = $src;

    // ---- (1) opener + single declare on line 2; drop later duplicates ----
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

    // ---- (2) Quote bare Refresh headers ----
    // Add opening quote after header(
    $src = preg_replace('/header\s*\(\s*Refresh\s*:/i', "header('Refresh:", $src);
    // Ensure there is a closing quote before the closing parenthesis
    $src = preg_replace("/(header\('Refresh:[^)]*)\)/i", "$1')", $src);

    // ---- (3) Fix broken $db init with comma tails ----
    // Pattern: "<db-assign> , <globals> ;"  (multiline, start-of-line safe)
    // Example it matches:
    //   $db = $container->get(Database::class);, $site_config, $CURUSER;
    // It will become:
    //   global $site_config, $CURUSER;
    //   $db = $container->get(Database::class);
    $src = preg_replace(
        '/(^[ \t]*\$db\s*=\s*\$container->get\s*\(\s*Database::class\s*\)\s*;\s*),\s*([$\w\s,]+)\s*;$/m',
        "global \\2;\n\\1",
        $src
    );

    // Normalize any "global $var1, $var2;" that accidentally lost dollars (defensive, should not be needed often)
    // e.g., "global site_config, CURUSER;" -> "global $site_config, $CURUSER;"
    $src = preg_replace_callback(
        '/^([ \t]*global[ \t]+)([^;]+);/m',
        function ($m) {
            $vars = preg_split('/\s*,\s*/', trim($m[2]));
            $fixed = [];
            foreach ($vars as $v) {
                $v = trim($v);
                if ($v === '') continue;
                if ($v[0] !== '$') {
                    $v = '$' . ltrim($v, '$');
                }
                $fixed[] = $v;
            }
            return $m[1] . implode(', ', $fixed) . ';';
        },
        $src
    );

    if ($src !== $orig) {
        file_put_contents($path, $src);
        $changed[] = basename($path);
    }
}

// ---- Write report ----
@mkdir($reportDir, 0777, true);
$summary  = "batch-43_7B admin autofix (safe set)\n";
$summary .= "Changed files: " . count($changed) . "\n";
foreach ($changed as $f) {
    $summary .= " - $f\n";
}
file_put_contents($report, $summary);
echo $summary;
