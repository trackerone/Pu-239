<?php declare(strict_types=1);
/**
 * Batch 43.7 — Admin full rewrite (no TODOs) — ROBUST HEADER FIX
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

    // --- 0) normaliser BOM/åbnings-tag
    $src = preg_replace('/^\xEF\xBB\xBF/', '', $src);
    if (!str_starts_with($src, "<?php")) {
        $src = "<?php\n" . ltrim($src);
    }

    // --- 1) FORCE: declare(strict_types=1) as very first statement
    // fjern alle eksisterende declare
    $src = preg_replace('/<\?php\s+declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i', "<?php\n", $src);
    // split i linjer og bygg header rigtigt
    $lines = explode("\n", $src);
    // fjern første linje hvis ikke er "<?php"
    if (trim($lines[0]) !== '<?php') {
        array_unshift($lines, '<?php');
    }
    // fjern evt. tomme linjer lige efter
    while (isset($lines[1]) && trim($lines[1]) === '') {
        array_splice($lines, 1, 1);
    }
    // indsæt declare på linje 2 hvis ikke allerede
    if (!isset($lines[1]) || stripos($lines[1], 'declare(strict_types=1);') === false) {
        array_splice($lines, 1, 0, 'declare(strict_types=1);');
        array_splice($lines, 2, 0, ''); // blank linje bagefter
    }
    $src = implode("\n", $lines);

    // --- 2) oprydning af hints/TODO41
    $src = str_replace('// $fluent removed — use $this->db (ExtendedPdo)', '// $fluent removed — use $db (ExtendedPdo)', $src);
    $src = preg_replace('/\s*\/\/\s*TODO\(batch41\):[^\r\n]*/', '', $src);

    // --- 3) this->db/cache → $db/$cache
    $src = preg_replace('/\$this->db->/', '$db->', $src);
    $src = preg_replace('/\$this->cache->/', '$cache->', $src);

    // --- 4) SIKKER placering for $db-init:
    //    efter declare + alle "use …;" + require_once …/runtime_safe.php
    if (!preg_match('/\$db\s*=\s*\$container->get\(Database::class\);/', $src)) {
        $src = insertDbInitRobust($src);
    }

    // hvis vi bruger $cache men ikke init
    if (strpos($src, '$cache->') !== false && !preg_match('/\$cache\s*=\s*\$container->get\(Cache::class\);/', $src)) {
        // indsæt $cache LIGE efter $db-init
        $src = preg_replace(
            '/(\$db\s*=\s*\$container->get\(Database::class\);\s*)/m',
            "$1" . "\$cache = \$container->get(Cache::class);\n",
            $src,
            1
        );
    }

    // fix dårlig ", $site_config;" hale, hvis den findes
    $src = preg_replace('/(\$db\s*=\s*\$container->get\(Database::class\);\s*),\s*\$site_config\s*;/', '$1', $src);

    // --- 5) linje-for-linje omskrivning (identisk med tidligere, forkortet her)
    $src = rewriteBody($src);

    if ($src !== $orig) {
        file_put_contents($path, $src);
        $changed++;
        $changedFiles[] = 'admin/' . $f->getFilename();
    }
}

// summary
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

/** ---------- helpers ---------- */

/**
 * Indsæt $db-init ETTER declare + alle use; og EFTER require_once runtime_safe.php, hvis den findes.
 * Falback: efter declare + alle use; hvis runtime_safe ikke findes.
 */
function insertDbInitRobust(string $src): string {
    $lines = explode("\n", $src);

    // find linjeindeks efter declare
    $i = 0; // '<?php'
    $i++;   // forventer declare på linje 2
    // hop tomme linjer
    while (isset($lines[$i]) && trim($lines[$i]) === '') $i++;

    // hop alle consecutive "use Foo\Bar;" linjer (de SKAL forblive før anden kode)
    $j = $i;
    while (isset($lines[$j]) && preg_match('/^\s*use\s+[^;]+;\s*$/', $lines[$j])) $j++;

    // find første require_once runtime_safe.php (skal være før $db-init, så $container findes)
    $k = $j;
    $runtimePos = null;
    for ($p = $j; $p < min($j + 40, count($lines)); $p++) {
        if (preg_match('#require_once\s+__DIR__\s*\.\s*\'/../include/runtime_safe\.php\'\s*;#', $lines[$p])) {
            $runtimePos = $p;
            break;
        }
    }

    $insertAt = ($runtimePos !== null) ? ($runtimePos + 1) : $j;

    array_splice($lines, $insertAt, 0, '$db = $container->get(Database::class);');

    return implode("\n", $lines);
}

/**
 * Omskriv krop — samme regler som før; kommentér kun BROKEN $db->run('); linjer.
 */
function rewriteBody(string $src): string {
    $lines = explode("\n", $src);
    $out   = [];
    $i = 0;
    while ($i < count($lines)) {
        $line = $lines[$i];

        // A) Ødelagt `$db->run(');` eller `if ($db->run(');`
        if (preg_match('/^\s*(?:if\s*\(\s*)?\$db->run\(\s*\'\s*\)\s*;\s*$/', $line)) {
            $out[] = '// ORIGINAL (broken): ' . trim($line);
            $i++; continue;
        }

        // B) SELECT COUNT(*)
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](SELECT\s+COUNT\([^)]+\).*?)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = rtrim($m[1]);
            $out[]  = '$count = (int) $db->fetchValue(\'' . $select . '\');';
            $j = $i + 1;
            while ($j < count($lines) && preg_match('/^\s*(\$row|\$count)\s*=|mysqli_fetch_(row|array)\s*\(/i', $lines[$j])) { $j++; }
            $i = $j; continue;
        }

        // C) SELECT … LIMIT 1
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](SELECT\s+.+?\s+LIMIT\s+1\s*)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = rtrim($m[1]);
            $out[]  = '$row = $db->fetchRow(\'' . $select . '\');';
            $i++;
            while ($i < count($lines) && preg_match('/^\s*\$row\s*=\s*mysqli_fetch_(assoc|array|row)\s*\(/i', $lines[$i])) $i++;
            continue;
        }

        // D) SELECT … + while (mysqli_fetch_assoc($res))
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

        // E) mysqli_num_rows($res)
        if (preg_match('/mysqli_num_rows\s*\(\s*\$res\s*\)/i', $line)) {
            $out[] = preg_replace('/mysqli_num_rows\s*\(\s*\$res\s*\)/i', 'is_array($rows) ? count($rows) : 0', $line);
            $i++; continue;
        }

        // F) $row = mysqli_fetch_*
        if (preg_match('/^\s*\$row\s*=\s*mysqli_fetch_(assoc|array|row)\s*\(\s*\$res\s*\)\s*;\s*$/i', $line)) {
            $out[] = '$row = $rows[0] ?? null;';
            $i++; continue;
        }

        // G) mysqli_insert_id()
        if (preg_match('/mysqli_insert_id\s*\(\s*\)/i', $line)) {
            $out[] = preg_replace('/mysqli_insert_id\s*\(\s*\)/i', '$db->lastInsertId()', $line);
            $i++; continue;
        }

        // H) INSERT/UPDATE/DELETE via sql_query
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](INSERT|UPDATE|DELETE)\b(.+)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $sql = rtrim($m[1] . $m[2]);
            $out[] = '$db->perform(\'' . $sql . '\');';
            $i++; continue;
        }

        $out[] = $line; $i++;
    }
    return implode("\n", $out);
}

/**
 * Indsæt $db lige efter declare + use-blok og (hvis fundet) efter require_once runtime_safe.php.
 * (Garanterer at $container eksisterer og at use-linjer forbliver først).
 */
function insertDbInitRobust(string $src): string {
    $lines = explode("\n", $src);

    // 1) find deklarationsstart (linje 0 = <?php, linje 1 = declare, skip tomme)
    $i = 2;
    while (isset($lines[$i]) && trim($lines[$i]) === '') $i++;

    // 2) hop ALLE top-use linjer
    while (isset($lines[$i]) && preg_match('/^\s*use\s+[^;]+;\s*$/', $lines[$i])) $i++;

    // 3) find runtime_safe require (efter use, men normalt meget tidligt)
    $runtimePos = null;
    for ($p = $i; $p < min($i + 60, count($lines)); $p++) {
        if (preg_match('#require_once\s+__DIR__\s*\.\s*\'/../include/runtime_safe\.php\'\s*;#', $lines[$p])) {
            $runtimePos = $p; break;
        }
    }

    $insertAt = ($runtimePos !== null) ? ($runtimePos + 1) : $i;

    array_splice($lines, $insertAt, 0, '$db = $container->get(Database::class);');

    return implode("\n", $lines);
}
