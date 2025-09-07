<?php
declare(strict_types=1);

/**
 * tools/batch-43_7B-admin-autofix.php
 *
 * Safe, deterministic fixes for admin/*.php:
 *  (A) Quote bare Refresh headers:
 *        header(Refresh: 2; url=...)  ->  header('Refresh: 2; url=...')
 *  (B) Fix broken $db init with comma tails (e.g. ", $site_config, $CURUSER;"):
 *        $db = $container->get(Database::class);, $site_config, $CURUSER;
 *          -> global $site_config, $CURUSER;
 *             $db = $container->get(Database::class);
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

    // ---- (A) Quote bare Refresh headers ----
    // 1) Sæt ' efter header(
    $src = preg_replace('/header\s*\(\s*Refresh\s*:/i', "header('Refresh:", $src);
    // 2) Sørg for afsluttende ' før )
    //    ... vi sætter kun en ' hvis der allerede ikke er en inden den første afsluttende ')'
    $src = preg_replace("/(header\('Refresh:[^']*)\)(\s*;)/i", "$1'$2", $src);

    // ---- (B) Fix $db init med komma-hale ----
    // Matcher linjer som:
    //   $db = $container->get(Database::class);, $site_config, $CURUSER;
    // Vi kræver at hele mønstret står på én linje, så vi ikke splitter noget komplekst.
    $lines = explode("\n", $src);
    $out   = [];
    $didB  = false;

    foreach ($lines as $line) {
        $trim = rtrim($line);

        // Simpel, robust detektion (case-insensitiv på "get")
        if (preg_match('/^\s*\$db\s*=\s*\$container->get\s*\(\s*Database::class\s*\)\s*;\s*,\s*([$\w\s,]+)\s*;\s*$/i', $trim, $m)) {
            // Split globals
            $raw = $m[1];
            $parts = preg_split('/\s*,\s*/', $raw);
            $keep  = [];

            if (is_array($parts)) {
                foreach ($parts as $p) {
                    $p = ltrim(trim($p), '$');
                    if ($p === '') {
                        continue;
                    }
                    // Kun lovlige/globalt kendte navne
                    if (in_array($p, ['site_config', 'CURUSER', 'container'], true)) {
                        $keep[] = '$' . $p;
                    }
                }
            }

            if (!empty($keep)) {
                $out[] = 'global ' . implode(', ', $keep) . ';';
            }
            $out[] = preg_replace('/,\s*([$\w\s,]+)\s*;\s*$/', ';', $trim); // fjern ", $...;" hale

            $didB = true;
            continue;
        }

        $out[] = $line;
    }

    if ($didB) {
        $src = implode("\n", $out);
    }

    if ($src !== $orig) {
        file_put_contents($path, $src);
        $changedFiles[] = basename($path);
    }
}

// ---- Rapport ----
$summary  = "batch-43_7B admin autofix (safe set)\n";
$summary .= "Changed files: " . count($changedFiles) . "\n";
foreach ($changedFiles as $f) {
    $summary .= " - $f\n";
}
file_put_contents($report, $summary);
echo $summary;
