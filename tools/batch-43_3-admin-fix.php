<?php declare(strict_types=1);
/**
 * Batch 43.3 — Admin hotfix:
 *  - Scope: admin/*.php
 *  - Replace `$this->db->` → `$db->`
 *  - Ensure `declare(strict_types=1);` is the very first statement
 *  - Ensure single `$db = $container->get(Database::class);` near the top
 *  - Clean up: remove TODO(batch41) comments; switch $fluent-hint to $db; fix broken ", $site_config;" tails
 *  - Remove broken `$db->run(');` fragments (leave clear TODO comment)
 *  - Write summary: tools/reports/batch43_3-summary.txt
 */

$root = getcwd();
$reportDir = $root . '/tools/reports';
@mkdir($reportDir, 0777, true);
$summaryPath = $reportDir . '/batch43_3-summary.txt';

$dir = $root . '/admin';
if (!is_dir($dir)) {
    echo "No admin/ directory found.\n";
    file_put_contents($summaryPath, "No admin/ directory found\n");
    exit(0);
}

$rii = new DirectoryIterator($dir);
$scanned = 0;
$changed = 0;
$changedFiles = [];

foreach ($rii as $entry) {
    if ($entry->isDot() || !$entry->isFile()) continue;
    if (pathinfo($entry->getFilename(), PATHINFO_EXTENSION) !== 'php') continue;

    $path = $entry->getPathname();
    $src  = file_get_contents($path);
    if ($src === false) continue;
    $orig = $src;
    $scanned++;

    // --- 1) strict_types must be first statement
    // Remove any existing declare and re-insert at very top after opening tag
    $src = preg_replace('/<\?php\s+declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i', '<?php' . PHP_EOL, $src, 1);
    if (str_starts_with($src, "<?php\n")) {
        // OK
    } elseif (str_starts_with($src, "<?php\r\n")) {
        // OK
    } elseif (str_starts_with($src, "<?php")) {
        $src = "<?php\n" . substr($src, 5);
    } else {
        $src = "<?php\n" . $src;
    }
    // Insert declare(strict_types=1);
    $src = preg_replace('/^<\?php\s*/', "<?php\ndeclare(strict_types=1);\n\n", $src, 1);

    // --- 2) remove TODO(batch41) comments
    $src = preg_replace('/\s*\/\/\s*TODO\(batch41\):[^\r\n]*/', '', $src);

    // --- 3) fix previous hint comment
    $src = str_replace('// $fluent removed — use $this->db (ExtendedPdo)', '// $fluent removed — use $db (ExtendedPdo)', $src);

    // --- 4) replace $this->db-> with $db->
    $src = preg_replace('/\$this->db->/', '$db->', $src);

    // --- 5) fix obviously broken "$db = ...), $site_config;" tail (keep globals explicit above)
    // turn: "$db = $container->get(Database::class);, $site_config;" into just "$db = $container->get(Database::class);"
    $src = preg_replace('/(\$db\s*=\s*\$container->get\(Database::class\);\s*),\s*\$site_config\s*;/', '$1', $src);

    // --- 6) ensure we have a single $db init near the top (after declare/use)
    if (!preg_match('/\$db\s*=\s*\$container->get\(Database::class\);/', $src)) {
        // Try to place after "use ..." block if any; else after declare
        if (preg_match('/^<\?php\s*declare\(strict_types=1\);\s*(?:\r?\n)+(?:use\s+[^\r\n;]+;\s*(?:\r?\n)+)*/', $src, $m)) {
            $pos = strlen($m[0]);
            $src = substr($src, 0, $pos) . "\n" . '$db = $container->get(Database::class);' . "\n" . substr($src, $pos);
        } else {
            // Fallback: right after declare
            $src = preg_replace('/^<\?php\s*declare\(strict_types=1\);\s*/', "<?php\ndeclare(strict_types=1);\n\n" . '$db = $container->get(Database::class);' . "\n", $src, 1);
        }
    }

    // --- 7) collapse "$x = $y = $db->..." → "$x = $db->..."
    $src = preg_replace('/\$(\w+)\s*=\s*\$(\w+)\s*=\s*(\$db->[^\;]+);/', '\$$1 = $3;', $src);

    // --- 8) remove broken `$db->run(');` fragments and leave a clear TODO
    $src = preg_replace('/\$db->run\(\s*\'\s*\);\s*/', '// TODO(batch43.3): previously broken $db->run(...) removed; supply proper SQL here.' . "\n", $src);

    if ($src !== $orig) {
        file_put_contents($path, $src);
        $changed++;
        $changedFiles[] = 'admin/' . $entry->getFilename();
    }
}

$summary  = "Batch 43.3 — Admin hotfix summary\n";
$summary .= "================================\n";
$summary .= "Files scanned:  {$scanned}\n";
$summary .= "Files changed:  {$changed}\n";
if ($changedFiles) {
    $summary .= "\nChanged files:\n";
    foreach ($changedFiles as $f) {
        $summary .= "  - {$f}\n";
    }
}
$summary .= "\nDate: " . gmdate('c') . "\n";

file_put_contents($summaryPath, $summary);
echo $summary;
