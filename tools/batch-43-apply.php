<?php declare(strict_types=1);
/**
 * Batch 43 — Report-driven FluentPDO → ExtendedPdo conversions (SAFE SET)
 *
 * Reads tools/reports/* (Batch 40/41/42 outputs) to decide which files to scan.
 * Applies conservative but REAL conversions:
 *
 *  A) by-id single row:
 *     $fluent->from('X')->where('id = ?', $id)->fetch();
 *        → $this->db->fetchRow("SELECT * FROM X WHERE id = :id", ['id' => (int)$id]);
 *
 *  B) ordered list:
 *     $fluent->from('X')->orderBy('ordered')->fetchAll();
 *        → $this->db->fetchAll("SELECT * FROM X ORDER BY ordered, id");
 *
 *  C) categories parents (with IF/COALESCE):
 *     → SELECT id,name,image,COALESCE(cat_desc,'') AS cat_desc, ordered
 *       FROM categories WHERE parent_id = 0 ORDER BY ordered, id
 *
 *  D) categories children by parent:
 *     → SELECT * FROM categories WHERE parent_id = :pid ORDER BY ordered, id
 *
 *  E) peers agents aggregation:
 *     → SELECT agent, LEFT(peer_id,8) AS peer_id FROM peers GROUP BY agent, LEFT(peer_id,8)
 *
 *  F) COUNT IN (two ids) on categories:
 *     → $this->db->fetchValue("SELECT COUNT(id) FROM categories WHERE id IN (:id1,:id2)", [...])
 *
 *  G) COUNT by FK (torrents.category):
 *     → $this->db->fetchValue("SELECT COUNT(id) FROM torrents WHERE category = :id", [...])
 *     + follow-up "if ($count)" → "if ($refCount > 0)"
 *
 *  H) delete category by id:
 *     → $this->db->perform("DELETE FROM categories WHERE id = :id", [...])
 *
 *  I) cleanup from Batch 40 retained:
 *     - remove `use Envms\FluentPDO\Literal;`
 *     - replace `@throws \Envms\FluentPDO\Exception` → `@throws \PDOException`
 *     - comment `$fluent = $container->get(Database::class);`
 *
 * Unknown/complex chains are left unchanged.
 */

$root = getcwd();
$reportDir = $root . '/tools/reports';
@mkdir($reportDir, 0777, true);

// Discover candidate files from any known report we created earlier
$reportGlobs = [
    $reportDir . '/batch40-*.ndjson',
    $reportDir . '/batch40_*-.ndjson',
    $reportDir . '/batch41-*.ndjson',
    $reportDir . '/batch42-fluentpdo-usage.ndjson',
    $reportDir . '/*.ndjson',
];

$candidateFiles = [];
foreach ($reportGlobs as $g) {
    foreach (glob($g) ?: [] as $path) {
        $fh = @fopen($path, 'r');
        if (!$fh) continue;
        while (!feof($fh)) {
            $line = trim((string)fgets($fh));
            if ($line === '') continue;
            $row = json_decode($line, true);
            if (!is_array($row)) continue;
            // Expect a "file" field
            if (!empty($row['file'])) {
                $candidateFiles[$row['file']] = true;
            }
        }
        fclose($fh);
    }
}

// Fallback: if no reports found, scan repo (php only)
if (empty($candidateFiles)) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $file) {
        if (!$file->isFile()) continue;
        if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) !== 'php') continue;
        $rel = ltrim(str_replace($root, '', $file->getPathname()), '/');
        $candidateFiles[$rel] = true;
    }
}

$changedFiles = 0;
$totalFiles   = 0;

foreach (array_keys($candidateFiles) as $rel) {
    // ignore non-php or vendor/static
    if (!preg_match('~\.php$~', $rel)) continue;
    if (preg_match('~(^|/)(vendor|node_modules|\.git|storage|public/uploads|public/cache|bootstrap/cache)/~', $rel)) continue;

    $abs = $root . '/' . $rel;
    if (!is_file($abs)) continue;

    $src = file_get_contents($abs);
    if ($src === false) continue;
    $orig = $src;
    $totalFiles++;

    // --- Cleanup we always keep ---
    $src = preg_replace('/^\s*use\s+Envms\\\\FluentPDO\\\\Literal;\s*$/m', '', $src);
    $src = preg_replace('/@throws\s+\\\\Envms\\\\FluentPDO\\\\Exception/', '@throws \\\\PDOException', $src);
    $src = preg_replace('/\$fluent\s*=\s*\$container->get\(Database::class\);/', '// $fluent removed — use $this->db (ExtendedPdo)', $src);

    // --- A) by-id single row ---
    $src = preg_replace(
        '#\$fluent->from\([\'"](?P<table>\w+)[\'"]\)\s*->where\([\'"]id\s*=\s*\?[\'"]\s*,\s*(?P<id>\$[A-Za-z0-9_\[\]\'">$]+)\s*\)\s*->fetch\(\);#',
        '$row = $this->db->fetchRow("SELECT * FROM ${1} WHERE id = :id", ["id" => (int) ${2}]);',
        $src
    );

    // --- B) ordered list ---
    $src = preg_replace(
        '#\$fluent->from\([\'"](?P<table>\w+)[\'"]\)\s*->orderBy\([\'"]ordered[\'"]\)\s*->fetchAll\(\);#',
        '$rows = $this->db->fetchAll("SELECT * FROM ${1} ORDER BY ordered, id");',
        $src
    );

    // --- C) categories parents ---
    $src = preg_replace(
        '#\$fluent->from\([\'"]categories[\'"]\)\s*->select\([\'"]IF\s*\(cat_desc\s*IS\s*NULL\s*,\s*""\s*,\s*cat_desc\)\s*AS\s*cat_desc[\'"]\)\s*->where\([\'"]parent_id\s*=\s*0[\'"]\)\s*->orderBy\([\'"]ordered[\'"]\)\s*->fetchAll\(\);#',
        '$parents = $this->db->fetchAll("SELECT id, name, image, COALESCE(cat_desc, \'\') AS cat_desc, ordered FROM categories WHERE parent_id = 0 ORDER BY ordered, id");',
        $src
    );

    // --- D) categories children by parent ---
    $src = preg_replace(
        '#\$fluent->from\([\'"]categories[\'"]\)\s*->where\([\'"]parent_id\s*=\s*\?[\'"]\s*,\s*(?P<pid>\$[A-Za-z0-9_]+)\)\s*->orderBy\([\'"]ordered[\'"]\)\s*->fetchAll\(\);#',
        '$children = $this->db->fetchAll("SELECT * FROM categories WHERE parent_id = :pid ORDER BY ordered, id", ["pid" => (int) ${1}]);',
        $src
    );

    // --- E) peers agents aggregation ---
    $src = preg_replace(
        '#\$agents\s*=\s*\$fluent->from\([\'"]peers[\'"]\)\s*->select\(null\)\s*->select\([\'"]agent[\'"]\)\s*->select\([\'"]LEFT\(peer_id\s*,\s*8\)\s+AS\s+peer_id[\'"]\)\s*->groupBy\([\'"]agent[\'"]\)\s*->groupBy\([\'"]peer_id[\'"]\)\s*->fetchAll\(\);#s',
        '$sql = "SELECT agent, LEFT(peer_id, 8) AS peer_id FROM peers GROUP BY agent, LEFT(peer_id, 8)";' . "\n" .
        '$agents = $this->db->fetchAll($sql);',
        $src
    );

    // --- F) COUNT IN (two ids) on categories ---
    $src = preg_replace(
        '#\$count\s*=\s*\$fluent->from\([\'"]categories[\'"]\)\s*->select\(null\)\s*->select\([\'"]COUNT\(id\)\s+AS\s+count[\'"]\)\s*->where\([\'"]id[\'"]\s*,\s*\[\s*(?P<a>\$[A-Za-z0-9_\[\]\'">$]+)\s*,\s*(?P<b>\$[A-Za-z0-9_\[\]\'">$]+)\s*\]\s*\)\s*->fetch\([\'"]count[\'"]\);#s',
        '$count = (int) $this->db->fetchValue("SELECT COUNT(id) FROM categories WHERE id IN (:id1, :id2)", ["id1" => (int) ${1}, "id2" => (int) ${2}]);',
        $src
    );

    // --- G) COUNT by FK (torrents.category) ---
    $src = preg_replace(
        '#\$count\s*=\s*\$fluent->from\([\'"]torrents[\'"]\)\s*->select\(null\)\s*->select\([\'"]COUNT\(id\)\s+AS\s+count[\'"]\)\s*->where\([\'"]category\s*=\s*\?[\'"]\s*,\s*(?P<cid>\$[A-Za-z0-9_]+)\)\s*->fetch\([\'"]count[\'"]\);#',
        '$refCount = (int) $this->db->fetchValue("SELECT COUNT(id) FROM torrents WHERE category = :id", ["id" => (int) ${1}]);',
        $src
    );
    $src = preg_replace('#if\s*\(\s*\$count\s*\)\s*\{#', 'if ($refCount > 0) {', $src);

    // --- H) delete category by id ---
    $src = preg_replace(
        '#\$fluent->deleteFrom\([\'"]categories[\'"]\)\s*->where\([\'"]id\s*=\s*\?[\'"]\s*,\s*(?P<did>\$[A-Za-z0-9_]+)\)\s*->execute\(\);#',
        '$this->db->perform("DELETE FROM categories WHERE id = :id", ["id" => (int) ${1}]);',
        $src
    );

    if ($src !== $orig) {
        file_put_contents($abs, $src);
        $changedFiles++;
    }
}

// Write change summary
$sumPath = $reportDir . '/batch43-summary.txt';
$body = "Batch 43 — SAFE conversions applied\n";
$body .= "=================================\n";
$body .= "Files considered: {$totalFiles}\n";
$body .= "Files changed:   {$changedFiles}\n";
$body .= "Date: " . gmdate('c') . "\n";
file_put_contents($sumPath, $body);

echo $body;
