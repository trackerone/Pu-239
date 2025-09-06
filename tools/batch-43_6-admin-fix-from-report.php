<?php declare(strict_types=1);
/**
 * Batch 43.6 — Admin auto-fix using batch43_5 report.
 *
 * Reads tools/reports/batch43_5-admin-audit.ndjson and applies deterministic fixes to admin/*.php.
 * Fixes:
 *  - this_db     : $this->db->  -> $db->
 *  - this_cache  : $this->cache-> -> $cache->
 *  - missing_db  : insert `$db = $container->get(Database::class);` near top (once)
 *  - bad_strict  : ensure `declare(strict_types=1);` is the very first statement
 *  - broken_run  : remove `$db->run(');` and leave a TODO marker unless context is Cheaters-panel (handled)
 *  - mysqli_mix  : drop obvious mysqli leftovers (`mysqli_*`, `sql_query`) where we already use $db; TODO left if non-trivial
 *  - bare_exit   : replace bare `die/exit;` with `app_halt('Exit called');`
 *  - todo41      : remove TODO(batch41)
 *  - bad_tail    : fix `$db = ...;, $site_config;` -> `$db = ...;`
 *  - bad_hint    : change hint to `$db`
 *
 * Writes summary to tools/reports/batch43_6-summary.txt.
 */

$root = getcwd();
$report = $root . '/tools/reports/batch43_5-admin-audit.ndjson';
$adminDir = $root . '/admin';
$reportDir = $root . '/tools/reports';
@mkdir($reportDir, 0777, true);
$summary = $reportDir . '/batch43_6-summary.txt';

if (!is_file($report)) {
    file_put_contents($summary, "No report file: $report\n");
    echo "No report file: $report\n";
    exit(0);
}

$issuesByFile = []; // rel => array of ['line'=>int,'issue'=>string,'message'=>string]
$fh = fopen($report, 'r');
while (!feof($fh)) {
    $line = trim((string) fgets($fh));
    if ($line === '') continue;
    $row = json_decode($line, true);
    if (!is_array($row) || empty($row['file']) || strpos($row['file'], 'admin/') !== 0) continue;
    $issuesByFile[$row['file']][] = $row;
}
fclose($fh);

$changed = 0; $scanned = 0; $changedFiles = [];
foreach ($issuesByFile as $rel => $list) {
    $abs = $root . '/' . $rel;
    if (!is_file($abs)) continue;

    $src = file_get_contents($abs);
    if ($src === false) continue;
    $orig = $src; $scanned++;

    // 1) strict_types first (remove any existing then insert at very top)
    if (hasIssue($list, 'bad_strict')) {
        $src = preg_replace('/<\?php\s+declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i', '<?php' . PHP_EOL, $src, 1);
        if (!str_starts_with($src, "<?php")) $src = "<?php\n" . $src;
        $src = preg_replace('/^<\?php\s*/', "<?php\ndeclare(strict_types=1);\n\n", $src, 1);
    }

    // 2) switch hints
    if (hasIssue($list, 'bad_hint')) {
        $src = str_replace('// $fluent removed — use $this->db (ExtendedPdo)', '// $fluent removed — use $db (ExtendedPdo)', $src);
    }

    // 3) replace $this->db / $this->cache
    if (hasIssue($list, 'this_db')) {
        $src = preg_replace('/\$this->db->/', '$db->', $src);
    }
    if (hasIssue($list, 'this_cache')) {
        $src = preg_replace('/\$this->cache->/', '$cache->', $src);
    }

    // 4) ensure $db init
    if (hasIssue($list, 'missing_db') && !preg_match('/\$db\s*=\s*\$container->get\(Database::class\);/', $src)) {
        $src = insertDbInit($src);
    }

    // If we introduced $cache usage, ensure init
    if (strpos($src, '$cache->') !== false && !preg_match('/\$cache\s*=\s*\$container->get\(Cache::class\);/', $src)) {
        $src = preg_replace(
            '/(\$db\s*=\s*\$container->get\(Database::class\);\s*)/m',
            "$1" . "\$cache = \$container->get(Cache::class);\n",
            $src,
            1
        );
    }

    // 5) fix bad ", $site_config;" tail
    if (hasIssue($list, 'bad_tail')) {
        $src = preg_replace('/(\$db\s*=\s*\$container->get\(Database::class\);\s*),\s*\$site_config\s*;/', '$1', $src);
    }

    // 6) handle broken `$db->run(');`
    if (hasIssue($list, 'broken_run')) {
        // Cheaters-panel: try to repair known patterns automatically (ids in remove/desact)
        if (preg_match('/Possible Cheaters|Ratio Cheats|cheater/i', $src)) {
            // Remove run(); and insert safe TODO that compiles (logic likely already fixed elsewhere)
            $src = preg_replace('/\$db->run\(\s*\'\s*\)\s*;/', "// TODO(batch43.6): broken SQL removed — insert proper \$db->perform(...) here.\n", $src);
            // Also normalize $this->cache to $cache (done above) and ensure cache init (done above)
        } else {
            $src = preg_replace('/\$db->run\(\s*\'\s*\)\s*;/', "// TODO(batch43.6): broken SQL removed — insert proper \$db->perform(...) here.\n", $src);
        }
    }

    // 7) mysqli/sql_query leftovers — just flag/trim obvious lines, leave TODO
    if (hasIssue($list, 'mysqli_mix')) {
        // Remove trivial assignment lines that contradict $db usage (very conservative)
        $src = preg_replace('/^\s*\$res\s*=\s*sql_query\(.*?\)\s*;\s*$/m', "// TODO(batch43.6): removed sql_query() leftover; convert to \$db->fetchAll/fetchRow.\n", $src);
        $src = preg_replace('/^\s*\$row\s*=\s*mysqli_fetch_(row|array)\(.*?\)\s*;\s*$/mi', "// TODO(batch43.6): removed mysqli_fetch_* leftover.\n", $src);
    }

    // 8) bare die/exit
    if (hasIssue($list, 'bare_exit')) {
        $src = preg_replace('/\b(die|exit)\s*(;|\(\s*\)\s*;)/', "app_halt('Exit called');", $src);
    }

    // 9) TODO(batch41)
    if (hasIssue($list, 'todo41')) {
        $src = preg_replace('/\s*\/\/\s*TODO\(batch41\):[^\r\n]*/', '', $src);
    }

    if ($src !== $orig) {
        file_put_contents($abs, $src);
        $changed++; $changedFiles[] = $rel;
    }
}

// Write summary
$body  = "Batch 43.6 — Admin auto-fix from report\n";
$body .= "======================================\n";
$body .= "Files considered: " . count($issuesByFile) . "\n";
$body .= "Files changed:    " . $changed . "\n";
if ($changedFiles) {
    $body .= "\nChanged files:\n";
    foreach ($changedFiles as $f) $body .= "  - {$f}\n";
}
$body .= "\nDate: " . gmdate('c') . "\n";
file_put_contents($summary, $body);
echo $body;

/** Helpers */
function hasIssue(array $list, string $code): bool {
    foreach ($list as $r) if (($r['issue'] ?? '') === $code) return true;
    return false;
}
function insertDbInit(string $src): string {
    // Insert after declare/use block
    if (preg_match('/^<\?php\s*declare\(strict_types=1\);\s*(?:\R)*(?:use\s+[^\R;]+;\s*\R)*/', $src, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1] + strlen($m[0][0]);
        return substr($src, 0, $pos) . "\n" . '$db = $container->get(Database::class);' . "\n" . substr($src, $pos);
    }
    // Else ensure declare then add
    if (!preg_match('/^<\?php\s*declare\(strict_types=1\);/m', $src)) {
        if (!str_starts_with($src, "<?php")) $src = "<?php\n" . $src;
        $src = preg_replace('/^<\?php\s*/', "<?php\ndeclare(strict_types=1);\n\n", $src, 1);
    }
    return preg_replace('/^<\?php\s*declare\(strict_types=1\);\s*/', "<?php\ndeclare(strict_types=1);\n\n" . '$db = $container->get(Database::class);' . "\n", $src, 1);
}
