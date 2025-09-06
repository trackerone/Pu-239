<?php declare(strict_types=1);
/**
 * Batch 43.7 — Admin full rewrite (no TODOs) — Robust header and DB init placement.
 *
 * What this does in admin/*.php (script files):
 *  - Assumes strict_types has already been made first by fix-strict-first.php.
 *  - Cleans leftover comments/hints.
 *  - Replaces $this->db/$this->cache -> $db/$cache (script context).
 *  - Inserts $db init AFTER declare, AFTER all "use ...;" lines, and AFTER
 *    require_once __DIR__.'/../include/runtime_safe.php'; so that $container exists.
 *  - If $cache is used, inserts $cache init right after $db init.
 *  - Converts common mysqli/sql_query patterns to ExtendedPdo:
 *      * SELECT COUNT(*) -> (int) $db->fetchValue(...)
 *      * SELECT ... LIMIT 1 -> $db->fetchRow(...)
 *      * SELECT + while(mysqli_fetch_assoc($res)) -> $rows = $db->fetchAll(...); foreach ($rows as $row) { ... }
 *      * mysqli_num_rows($res) -> is_array($rows) ? count($rows) : 0
 *      * mysqli_fetch_*($res) -> $row = $rows[0] ?? null;
 *      * INSERT/UPDATE/DELETE via sql_query -> $db->perform(...)
 *  - Broken lines like "$db->run(');" are commented as ORIGINAL (no TODOs).
 *  - Keeps pager LIMIT concatenation intact (e.g. ... ' . $pager['limit']).
 *
 * Writes summary to tools/reports/batch43_7-summary.txt
 */

$root = getcwd();
$admin = $root . '/admin';
$reportDir = $root . '/tools/reports';
@mkdir($reportDir, 0777, true);
$summaryPath = $reportDir . '/batch43_7-summary.txt';

if (!is_dir($admin)) {
    file_put_contents($summaryPath, "No admin/ directory found\n");
    exit(0);
}

$changed = 0;
$scanned = 0;
$changedFiles = [];

$it = new DirectoryIterator($admin);
foreach ($it as $f) {
    if ($f->isDot() || !$f->isFile()) continue;
    if (strtolower($f->getExtension()) !== 'php') continue;

    $path = $f->getPathname();
    $src  = file_get_contents($path);
    if ($src === false) continue;
    $orig = $src;
    $scanned++;

    // Normalize BOM and ensure opener; strict_types is already handled by fix-strict-first.php
    $src = preg_replace('/^\xEF\xBB\xBF/', '', $src);
    if (!str_starts_with($src, "<?php")) {
        $src = "<?php\n" . ltrim($src);
    }

    // 1) Clean up hints / TODO(batch41)
    $src = str_replace('// $fluent removed — use $this->db (ExtendedPdo)', '// $fluent removed — use $db (ExtendedPdo)', $src);
    $src = preg_replace('/\s*\/\/\s*TODO\(batch41\):[^\r\n]*/', '', $src);

    // 2) Replace $this->db/$this->cache in script context
    $src = preg_replace('/\$this->db->/', '$db->', $src);
    $src = preg_replace('/\$this->cache->/', '$cache->', $src);

    // 3) Insert $db init AFTER declare, AFTER "use", and AFTER runtime_safe.php
    if (!preg_match('/\$db\s*=\s*\$container->get\(Database::class\);/', $src)) {
        $src = insertDbInitRobust($src);
    }

    // 4) Insert $cache init if referenced
    if (strpos($src, '$cache->') !== false && !preg_match('/\$cache\s*=\s*\$container->get\(Cache::class\);/', $src)) {
        $src = preg_replace(
            '/(\$db\s*=\s*\$container->get\(Database::class\);\s*)/m',
            "$1" . "\$cache = \$container->get(Cache::class);\n",
            $src,
            1
        );
    }

    // 5) Remove any bad trailing ", $site_config;" after $db init
    $src = preg_replace('/(\$db\s*=\s*\$container->get\(Database::class\);\s*),\s*\$site_config\s*;/', '$1', $src);

    // 6) Rewrite body patterns
    $src = rewriteBody($src);

    if ($src !== $orig) {
        file_put_contents($path, $src);
        $changed++;
        $changedFiles[] = 'admin/' . $f->getFilename();
    }
}

// Summary
$sum  = "Batch 43.7 — Admin full rewrite (no TODOs, robust header)\n";
$sum .= "=========================================================\n";
$sum .= "Files scanned:  {$scanned}\n";
$sum .= "Files changed:  {$changed}\n";
if ($changedFiles) {
    $sum .= "\nChanged files:\n";
    foreach ($changedFiles as $cf) $sum .= "  - {$cf}\n";
}
$sum .= "\nDate: " . gmdate('c') . "\n";
file_put_contents($summaryPath, $sum);
echo $sum;

/** ---------------- Helpers ---------------- */

/**
 * Insert `$db = $container->get(Database::class);` after:
 *   - declare(strict_types=1);
 *   - all top-level `use ...;` lines
 *   - the first `require_once __DIR__.'/../include/runtime_safe.php';` (if present)
 * This guarantees that $container exists and `use` statements remain before code.
 */
function insertDbInitRobust(string $src): string {
    $lines = explode("\n", $src);

    // 1) Find "start" after declare (line 0 = <?php, line 1 = declare)
    $i = 2;
    // Skip blank lines
    while (isset($lines[$i]) && trim($lines[$i]) === '') $i++;

    // 2) Skip top `use Foo\Bar;` lines
    while (isset($lines[$i]) && preg_match('/^\s*use\s+[^;]+;\s*$/', $lines[$i])) $i++;

    // 3) Find runtime_safe require to place $db after it, if present
    $runtimePos = null;
    for ($p = $i; $p < min($i + 120, count($lines)); $p++) {
        if (preg_match('#require_once\s+__DIR__\s*\.\s*\'/../include/runtime_safe\.php\'\s*;#', $lines[$p])) {
            $runtimePos = $p; break;
        }
    }
    $insertAt = ($runtimePos !== null) ? ($runtimePos + 1) : $i;

    // Avoid inserting twice (just in case)
    if (!preg_grep('/^\s*\$db\s*=\s*\$container->get\(Database::class\);\s*$/', $lines)) {
        array_splice($lines, $insertAt, 0, '$db = $container->get(Database::class);');
    }

    return implode("\n", $lines);
}

/**
 * Rewrite common mysqli/sql_query patterns into ExtendedPdo calls.
 * Also comments broken `$db->run(');` lines (keeps ORIGINAL line, no TODOs).
 */
function rewriteBody(string $src): string {
    $lines = explode("\n", $src);
    $out = [];
    $i = 0;

    while ($i < count($lines)) {
        $line = $lines[$i];

        // Broken `$db->run(');` (or wrapped in if)
        if (preg_match('/^\s*(?:if\s*\(\s*)?\$db->run\(\s*\'\s*\)\s*;\s*$/', $line)) {
            $out[] = '// ORIGINAL (broken): ' . trim($line);
            $i++; continue;
        }

        // SELECT COUNT(*)
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](SELECT\s+COUNT\([^)]+\).*?)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = rtrim($m[1]);
            $out[]  = '$count = (int) $db->fetchValue(\'' . $select . '\');';
            // eat following $row=.../mysqli_fetch_row/array lines related to the count
            $j = $i + 1;
            while ($j < count($lines) && preg_match('/^\s*(\$row|\$count)\s*=|mysqli_fetch_(row|array)\s*\(/i', $lines[$j])) { $j++; }
            $i = $j; continue;
        }

        // SELECT ... LIMIT 1
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](SELECT\s+.+?\s+LIMIT\s+1\s*)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = rtrim($m[1]);
            $out[]  = '$row = $db->fetchRow(\'' . $select . '\');';
            $i++;
            // drop a following mysqli_fetch_* row assignment
            while ($i < count($lines) && preg_match('/^\s*\$row\s*=\s*mysqli_fetch_(assoc|array|row)\s*\(/i', $lines[$i])) $i++;
            continue;
        }

        // SELECT + while(mysqli_fetch_assoc($res))
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](SELECT\s+.+)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = rtrim($m[1]);
            $j = $i + 1;
            if ($j < count($lines) && preg_match('/^\s*while\s*\(\s*mysqli_fetch_assoc\s*\(\s*\$res\s*\)\s*as?\s*\$([A-Za-z_]\w*)\s*\)\s*\{\s*$/i', $lines[$j], $wm)) {
                $rowVar = $wm[1];
                $out[]  = '$rows = $db->fetchAll(\'' . $select . '\');';
                $out[]  = 'foreach ($rows as $' . $rowVar . ') {';
                $j++;
                while ($j < count($lines) && !preg_match('/^\s*\}\s*$/', $lines[$j])) { $out[] = $lines[$j]; $j++; }
                if ($j < count($lines)) { $out[] = $lines[$j]; $j++; }
                $i = $j; continue;
            } else {
                $out[] = '$rows = $db->fetchAll(\'' . $select . '\');';
                $i++; continue;
            }
        }

        // mysqli_num_rows($res)
        if (preg_match('/mysqli_num_rows\s*\(\s*\$res\s*\)/i', $line)) {
            $out[] = preg_replace('/mysqli_num_rows\s*\(\s*\$res\s*\)/i', 'is_array($rows) ? count($rows) : 0', $line);
            $i++; continue;
        }

        // $row = mysqli_fetch_*
        if (preg_match('/^\s*\$row\s*=\s*mysqli_fetch_(assoc|array|row)\s*\(\s*\$res\s*\)\s*;\s*$/i', $line)) {
            $out[] = '$row = $rows[0] ?? null;';
            $i++; continue;
        }

        // mysqli_insert_id()
        if (preg_match('/mysqli_insert_id\s*\(\s*\)/i', $line)) {
            $out[] = preg_replace('/mysqli_insert_id\s*\(\s*\)/i', '$db->lastInsertId()', $line);
            $i++; continue;
        }

        // INSERT/UPDATE/DELETE via sql_query
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](INSERT|UPDATE|DELETE)\b(.+)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $sql = rtrim($m[1] . $m[2]);
            $out[] = '$db->perform(\'' . $sql . '\');';
            $i++; continue;
        }

        // default: passthrough
        $out[] = $line; $i++;
    }

    return implode("\n", $out);
}
