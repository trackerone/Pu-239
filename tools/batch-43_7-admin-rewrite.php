<?php declare(strict_types=1);
/**
 * Batch 43.7 — Admin full rewrite (no TODOs)
 *
 * Scope: admin/*.php (non-class scripts)
 * Converts old MySQL/Fluent leftovers to ExtendedPdo without leaving TODOs:
 *  - strict_types first
 *  - ensure $db init (and $cache if referenced)
 *  - $this->db-> => $db-> ; $this->cache-> => $cache->
 *  - sql_query('SELECT …') + mysqli_fetch_assoc loop => $rows = $db->fetchAll('…'); foreach ($rows as $row) …
 *  - SELECT COUNT(*) => $db->fetchValue('SELECT COUNT(*) …')
 *  - SELECT … LIMIT 1 => $db->fetchRow('… LIMIT 1')
 *  - mysqli_num_rows($res) => is_array($rows) ? count($rows) : 0
 *  - mysqli_fetch_array/row => use fetchRow()/fetchValue() depending on context
 *  - mysqli_insert_id() => $db->lastInsertId()
 *  - broken `$db->run(');` lines: comment out with ORIGINAL line kept (no TODO)
 *  - keep pager LIMIT concatenation as-is (e.g., … ' . $pager['limit'])
 *  - remove TODO(batch41) and wrong hints
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
    if (pathinfo($f->getFilename(), PATHINFO_EXTENSION) !== 'php') continue;

    $path = $f->getPathname();
    $src  = file_get_contents($path);
    if ($src === false) continue;
    $orig = $src;
    $scanned++;

    // 1) strict_types first
    $src = preg_replace('/<\?php\s+declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i', '<?php' . PHP_EOL, $src, 1);
    if (!str_starts_with($src, "<?php")) $src = "<?php\n" . $src;
    $src = preg_replace('/^<\?php\s*/', "<?php\ndeclare(strict_types=1);\n\n", $src, 1);

    // 2) hint fixes & remove TODO(batch41)
    $src = str_replace('// $fluent removed — use $this->db (ExtendedPdo)', '// $fluent removed — use $db (ExtendedPdo)', $src);
    $src = preg_replace('/\s*\/\/\s*TODO\(batch41\):[^\r\n]*/', '', $src);

    // 3) $this->db/cache → $db/$cache
    $src = preg_replace('/\$this->db->/', '$db->', $src);
    $src = preg_replace('/\$this->cache->/', '$cache->', $src);

    // 4) Ensure $db init (and $cache if referenced)
    if (!preg_match('/\$db\s*=\s*\$container->get\(Database::class\);/', $src)) {
        $src = insertDbInit($src);
    }
    if (strpos($src, '$cache->') !== false && !preg_match('/\$cache\s*=\s*\$container->get\(Cache::class\);/', $src)) {
        $src = preg_replace(
            '/(\$db\s*=\s*\$container->get\(Database::class\);\s*)/m',
            "$1" . "\$cache = \$container->get(Cache::class);\n",
            $src,
            1
        );
    }
    // fix bad ", $site_config;" tail, if any
    $src = preg_replace('/(\$db\s*=\s*\$container->get\(Database::class\);\s*),\s*\$site_config\s*;/', '$1', $src);

    // 5) Rewrite sql_query()/mysqli_* patterns line-by-line
    $lines = explode("\n", $src);
    $out   = [];
    $i = 0;
    while ($i < count($lines)) {
        $line = $lines[$i];

        // A) broken `$db->run(');` → comment original (no TODO)
        if (preg_match('/^\s*(if\s*\(\s*)?\$db->run\(\s*\'\s*\)\s*;\s*$/', $line, $m)) {
            $out[] = '// ORIGINAL (broken): ' . trim($line);
            $i++; continue;
        }

        // B) sql_query('SELECT COUNT(*) …') … mysqli_fetch_array/row → fetchValue
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](SELECT\s+COUNT\([^\)]*\).*?)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            // lookahead for a $row= mysqli_fetch_* and $count assignment; we replace those too
            $select = $m[1];
            $out[] = '$count = (int) $db->fetchValue(\'' . rtrim($select) . '\');';
            // skip following mysqli_fetch_* lines if they directly read from $res into $row/$count
            $j = $i + 1;
            while ($j < count($lines) && preg_match('/^\s*\$(row|count)\s*=|mysqli_fetch_(row|array)\s*\(/i', $lines[$j])) {
                $j++;
            }
            $i = $j; continue;
        }

        // C) sql_query('SELECT … LIMIT 1') … mysqli_fetch_assoc/array → fetchRow
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](SELECT\s+.+?\s+LIMIT\s+1\s*)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = $m[1];
            $out[]  = '$row = $db->fetchRow(\'' . rtrim($select) . '\');';
            // drop a following $row = mysqli_fetch_* if present
            $i++;
            while ($i < count($lines) && preg_match('/^\s*\$row\s*=\s*mysqli_fetch_(assoc|array|row)\s*\(/i', $lines[$i])) {
                $i++;
            }
            continue;
        }

        // D) sql_query('SELECT …') + while (mysqli_fetch_assoc($res)) → fetchAll + foreach
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](SELECT\s+.+)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = $m[1];
            // collect while loop if immediately follows
            $j = $i + 1;
            if ($j < count($lines) && preg_match('/^\s*while\s*\(\s*mysqli_fetch_assoc\s*\(\s*\$res\s*\)\s*as?\s*\$([A-Za-z_]\w*)\s*\)\s*\{\s*$/i', $lines[$j], $wm)) {
                $rowVar = $wm[1];
                $out[]  = '$rows = $db->fetchAll(\'' . rtrim($select) . '\');';
                $out[]  = 'foreach ($rows as $' . $rowVar . ') {';
                // copy loop body until closing brace
                $j++;
                while ($j < count($lines) && !preg_match('/^\s*\}\s*$/', $lines[$j])) {
                    $out[] = $lines[$j];
                    $j++;
                }
                if ($j < count($lines)) { $out[] = $lines[$j]; $j++; } // append closing brace
                $i = $j; continue;
            } else {
                // no while: just fetchAll into $rows
                $out[] = '$rows = $db->fetchAll(\'' . rtrim($select) . '\');';
                $i++; continue;
            }
        }

        // E) mysqli_num_rows($res) → count($rows) (assumes D created $rows)
        if (preg_match('/mysqli_num_rows\s*\(\s*\$res\s*\)/i', $line)) {
            $out[] = preg_replace('/mysqli_num_rows\s*\(\s*\$res\s*\)/i', 'is_array($rows) ? count($rows) : 0', $line);
            $i++; continue;
        }

        // F) $row = mysqli_fetch_array/assoc/row($res) → assume $rows[0] if $rows exists; else fetchRow
        if (preg_match('/^\s*\$row\s*=\s*mysqli_fetch_(assoc|array|row)\s*\(\s*\$res\s*\)\s*;\s*$/i', $line)) {
            // If we already created $rows above, map to first row
            $out[] = '$row = isset($rows[0]) ? $rows[0] : null;';
            $i++; continue;
        }

        // G) mysqli_insert_id() → $db->lastInsertId()
        if (preg_match('/mysqli_insert_id\s*\(\s*\)/i', $line)) {
            $out[] = preg_replace('/mysqli_insert_id\s*\(\s*\)/i', '$db->lastInsertId()', $line);
            $i++; continue;
        }

        // H) plain sql_query('INSERT|UPDATE|DELETE …') → $db->perform('…')
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](INSERT|UPDATE|DELETE)\b(.+)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $sql = $m[1] . $m[2];
            $out[] = '$db->perform(\'' . rtrim($sql) . '\');';
            $i++; continue;
        }

        // default: copy line
        $out[] = $line;
        $i++;
    }

    $new = implode("\n", $out);
    if ($new !== $orig) {
        file_put_contents($path, $new);
        $changed++;
        $changedFiles[] = 'admin/' . $f->getFilename();
    }
}

// summary
$sum  = "Batch 43.7 — Admin full rewrite (no TODOs)\n";
$sum .= "=========================================\n";
$sum .= "Files scanned:  {$scanned}\n";
$sum .= "Files changed:  {$changed}\n";
if ($changedFiles) {
    $sum .= "\nChanged files:\n";
    foreach ($changedFiles as $cf) $sum .= "  - {$cf}\n";
}
$sum .= "\nDate: " . gmdate('c') . "\n";
file_put_contents($summaryPath, $sum);
echo $sum;

/** helpers */
function insertDbInit(string $src): string {
    // after declare/use block
    if (preg_match('/^<\?php\s*declare\(strict_types=1\);\s*(?:\R)*(?:use\s+[^\R;]+;\s*\R)*/', $src, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1] + strlen($m[0][0]);
        return substr($src, 0, $pos) . "\n" . '$db = $container->get(Database::class);' . "\n" . substr($src, $pos);
    }
    return preg_replace('/^<\?php\s*declare\(strict_types=1\);\s*/', "<?php\ndeclare(strict_types=1);\n\n" . '$db = $container->get(Database::class);' . "\n", $src, 1);
}
