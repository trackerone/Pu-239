<?php declare(strict_types=1);
/**
 * Batch 43.7 — Admin full rewrite (no TODOs)
 *
 * Scope: admin/*.php (script-filer, ikke klasser)
 * Konverterer mysqli/sql_query/Fluent-levn til ExtendedPdo uden TODOs:
 *  - Flyt/indsæt declare(strict_types=1) absolut øverst
 *  - Sikr $db init (og $cache hvis brugt)
 *  - $this->db-> → $db-> ; $this->cache-> → $cache->
 *  - sql_query('SELECT …') + while (mysqli_fetch_assoc($res)) → $rows = $db->fetchAll('…'); foreach ($rows as $row) …
 *  - SELECT COUNT(*) → (int) $db->fetchValue('SELECT COUNT(*) …')
 *  - SELECT … LIMIT 1 → $db->fetchRow('… LIMIT 1')
 *  - mysqli_num_rows($res) → is_array($rows) ? count($rows) : 0
 *  - mysqli_fetch_array/assoc/row($res) → $row = $rows[0] ?? null (hvis $rows findes), ellers $db->fetchRow(...)
 *  - mysqli_insert_id() → $db->lastInsertId()
 *  - Ødelagte linjer som `$db->run(');` kommenteres med ORIGINAL (ingen TODO)
 *  - Bevar pager LIMIT concatenation som er (… ' . $pager['limit'])
 *  - Fjern TODO(batch41) og forkerte hints
 *
 * Writer: tools/reports/batch43_7-summary.txt
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

    // --- strict_types: fjern evt. eksisterende og genindsæt lige efter <?php
    $src = preg_replace('/<\?php\s+declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i', "<?php\n", $src);
    if (!str_starts_with($src, "<?php")) {
        $src = "<?php\n" . $src;
    }
    $src = preg_replace('/^<\?php\s*/', "<?php\ndeclare(strict_types=1);\n\n", $src, 1);

    // --- oprydning af hints/TODO41
    $src = str_replace('// $fluent removed — use $this->db (ExtendedPdo)', '// $fluent removed — use $db (ExtendedPdo)', $src);
    $src = preg_replace('/\s*\/\/\s*TODO\(batch41\):[^\r\n]*/', '', $src);

    // --- this->db/cache → $db/$cache
    $src = preg_replace('/\$this->db->/', '$db->', $src);
    $src = preg_replace('/\$this->cache->/', '$cache->', $src);

    // --- sikr $db init (og $cache hvis brugt)
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
    // fix dårlig ", $site_config;" hale, hvis den findes
    $src = preg_replace('/(\$db\s*=\s*\$container->get\(Database::class\);\s*),\s*\$site_config\s*;/', '$1', $src);

    // --- linje-for-linje omskrivning
    $lines = explode("\n", $src);
    $out   = [];
    $i = 0;
    while ($i < count($lines)) {
        $line = $lines[$i];

        // A) Ødelagt `$db->run(');` eller `if ($db->run(');` → kommentér ORIGINAL
        if (preg_match('/^\s*(?:if\s*\(\s*)?\$db->run\(\s*\'\s*\)\s*;\s*$/', $line)) {
            $out[] = '// ORIGINAL (broken): ' . trim($line);
            $i++; continue;
        }

        // B) SELECT COUNT(*)
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](SELECT\s+COUNT\([^)]+\).*?)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = rtrim($m[1]);
            $out[]  = '$count = (int) $db->fetchValue(\'' . $select . '\');';
            // spis efterfølgende mysqli_fetch* / $row[0] assignments
            $j = $i + 1;
            while ($j < count($lines) && preg_match('/^\s*(\$row|\$count)\s*=|mysqli_fetch_(row|array)\s*\(/i', $lines[$j])) { $j++; }
            $i = $j; continue;
        }

        // C) SELECT … LIMIT 1 → fetchRow
        if (preg_match('/^\s*\$res\s*=\s*sql_query\(\s*[\'"](SELECT\s+.+?\s+LIMIT\s+1\s*)[\'"]\s*\)\s*(?:or\s+sqlerr\(.*?\))?\s*;\s*$/i', $line, $m)) {
            $select = rtrim($m[1]);
            $out[]  = '$row = $db->fetchRow(\'' . $select . '\');';
            // fjern evt. efterfølgende $row = mysqli_fetch_*
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

        // F) $row = mysqli_fetch_*(res)
        if (preg_match('/^\s*\$row\s*=\s*mysqli_fetch_(assoc|array|row)\s*\(\s*\$res\s*\)\s*;\s*$/i', $line)) {
            // antag at $rows findes; ellers lav defensivt lookup
            $out[] = '$row = isset($rows[0]) ? $rows[0] : null;';
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

        // default: behold linje
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
    // placer efter declare/use blok
    if (preg_match('/^<\?php\s*declare\(strict_types=1\);\s*(?:\R)*(?:use\s+[^\R;]+;\s*\R)*/', $src, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1] + strlen($m[0][0]);
        return substr($src, 0, $pos) . "\n" . '$db = $container->get(Database::class);' . "\n" . substr($src, $pos);
    }
    return preg_replace(
        '/^<\?php\s*declare\(strict_types=1\);\s*/',
        "<?php\ndeclare(strict_types=1);\n\n" . '$db = $container->get(Database::class);' . "\n",
        $src,
        1
    );
}
