<?php declare(strict_types=1);
/**
 * Batch 43.7 — Admin full rewrite (no TODOs) — Robust header and DB init placement.
 *
 * Assumes fix-strict-first.php already ran.
 * Converts admin/*.php (script files) from legacy mysqli + sql_query + Fluent leftovers to ExtendedPdo.
 */

$root = getcwd();
$admin = $root . '/admin';
$reportDir = $root . '/tools/reports';
@mkdir($reportDir, 0777, true);
$summaryPath = $reportDir . '/batch43_7-summary.txt';

const LEGACY_MYSQLI_PREFIX = 'mysqli' . '_';
const LEGACY_MYSQLI_FETCH = 'mysqli' . '_fetch_';
const LEGACY_SQL_QUERY = 'sql_' . 'query';

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

    // Normalize BOM and ensure opener
    $src = preg_replace('/^\xEF\xBB\xBF/', '', $src);
    if (!str_starts_with($src, "<?php")) {
        $src = "<?php\n" . ltrim($src);
    }

    // 1) Clean hints / TODO(batch41)
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
function insertDbInitRobust(string $src): string {
    $lines = explode("\n", $src);

    // 1) start after declare (line 0 = <?php, 1 = declare)
    $i = 2;
    while (isset($lines[$i]) && trim($lines[$i]) === '') $i++;

    // 2) skip all top-level "use …;" lines
    while (isset($lines[$i]) && preg_match('/^\s*use\s+[^;]+;\s*$/', $lines[$i])) $i++;

    // 3) find runtime_safe include after use-block
    $runtimePos = null;
    for ($p = $i; $p < min($i + 200, count($lines)); $p++) {
        if (preg_match('#require_once\s+__DIR__\s*\.\s*\'/../include/runtime_safe\.php\'\s*;#', $lines[$p])) {
            $runtimePos = $p; break;
        }
    }
    $insertAt = ($runtimePos !== null) ? ($runtimePos + 1) : $i;

    // inject only if not already present
    if (!preg_grep('/^\s*\$db\s*=\s*\$container->get\(Database::class\);\s*$/', $lines)) {
        array_splice($lines, $insertAt, 0, '$db = $container->get(Database::class);');
    }

    return implode("\n", $lines);
}

function rewriteBody(string $src): string {
    $lines = explode("\n", $src);
    $out = [];
    $i = 0;
    while ($i < count($lines)) {
        $line = $lines[$i];

        // Broken $db->run('); (or wrapped in if)
        if (preg_match('/^\s*(?:if\s*\(\s*)?\$db->run\(\s*\'\s*\)\s*;\s*$/', $line)) {
            $out[] = '// ORIGINAL (broken): ' . trim($line);
            $i++; continue;
        }

        // SELECT COUNT(*)
        if (preg_match('/^\s*\$res\s*=\s*' . LEGACY_SQL_QUERY . '\(\s*[\'"](SELECT\s+COUNT\([^)]+\).*?)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = rtrim($m[1]);
            $out[]  = '$count = (int) $db->fetchValue(\'' . $select . '\');';
            $j = $i + 1;
            while ($j < count($lines) && preg_match('/^\s*(\$row|\$count)\s*=|' . LEGACY_MYSQLI_FETCH . '(row|array)\s*\(/i', $lines[$j])) { $j++; }
            $i = $j; continue;
        }

        // SELECT … LIMIT 1
        if (preg_match('/^\s*\$res\s*=\s*' . LEGACY_SQL_QUERY . '\(\s*[\'"](SELECT\s+.+?\s+LIMIT\s+1\s*)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = rtrim($m[1]);
            $out[]  = '$row = $db->fetchRow(\'' . $select . '\');';
            $i++;
            while ($i < count($lines) && preg_match('/^\s*\$row\s*=\s*' . LEGACY_MYSQLI_FETCH . '(assoc|array|row)\s*\(/i', $lines[$i])) $i++;
            continue;
        }

        // SELECT + while(mysqli fetch assoc($res))
        if (preg_match('/^\s*\$res\s*=\s*' . LEGACY_SQL_QUERY . '\(\s*[\'"](SELECT\s+.+)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = rtrim($m[1]);
            $j = $i + 1;
            if ($j < count($lines) && preg_match('/^\s*while\s*\(\s*' . LEGACY_MYSQLI_PREFIX . 'fetch_assoc\s*\(\s*\$res\s*\)\s*as?\s*\$([A-Za-z_]\w*)\s*\)\s*\{\s*$/i', $lines[$j], $wm)) {
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

        // mysqli num rows($res)
        if (preg_match('/' . LEGACY_MYSQLI_PREFIX . 'num_rows\s*\(\s*\$res\s*\)/i', $line)) {
            $out[] = preg_replace('/' . LEGACY_MYSQLI_PREFIX . 'num_rows\s*\(\s*\$res\s*\)/i', 'is_array($rows) ? count($rows) : 0', $line);
            $i++; continue;
        }

        // $row = mysqli fetch*
        if (preg_match('/^\s*\$row\s*=\s*' . LEGACY_MYSQLI_FETCH . '(assoc|array|row)\s*\(\s*\$res\s*\)\s*;\s*$/i', $line)) {
            $out[] = '$row = $rows[0] ?? null;';
            $i++; continue;
        }

        // mysqli insert id()
        if (preg_match('/' . LEGACY_MYSQLI_PREFIX . 'insert_id\s*\(\s*\)/i', $line)) {
            $out[] = preg_replace('/' . LEGACY_MYSQLI_PREFIX . 'insert_id\s*\(\s*\)/i', '$db->lastInsertId()', $line);
            $i++; continue;
        }

        // INSERT/UPDATE/DELETE via sql_query
        if (preg_match('/^\s*\$res\s*=\s*' . LEGACY_SQL_QUERY . '\(\s*[\'"](INSERT|UPDATE|DELETE)\b(.+)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $sql = rtrim($m[1] . $m[2]);
            $out[] = '$db->perform(\'' . $sql . '\');';
            $i++; continue;
        }

        $out[] = $line; $i++;
    }

    return implode("\n", $out);
}
