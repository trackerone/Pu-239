<?php declare(strict_types=1);
/**
 * Batch 39 transformer
 * Scope: admin/ PHP files
 * Goal: Remove FluentPDO leftovers in admin (peers/agents, categories lists/children), remove Literal import,
 *       replace common FluentPDO patterns with Aura ExtendedPdo calls, and mark generic updates with TODO.
 * Notes: Conservative regex replaces only well-known patterns to avoid false positives.
 */

$root = getcwd();
$adminDir = $root . '/admin';

if (!is_dir($adminDir)) {
    fwrite(STDERR, "Admin directory not found at: $adminDir\n");
    exit(0); // do not fail the action
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($adminDir));
$changed = 0;

foreach ($files as $file) {
    if (!$file->isFile()) continue;
    if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) !== 'php') continue;

    $path = $file->getPathname();
    $original = file_get_contents($path);
    $updated = $original;

    // 0) Remove use Envms\FluentPDO\Literal;
    $updated = preg_replace('/^\s*use\s+Envms\\\\FluentPDO\\\\Literal;\s*$/m', '', $updated);

    // 1) Replace @throws \Envms\FluentPDO\Exception → @throws \PDOException
    $updated = preg_replace('/@throws\s+\\\\Envms\\\\FluentPDO\\\\Exception/', '@throws \\\\PDOException', $updated);

    // 2) Remove "$fluent = $container->get(Database::class);" hints
    $updated = preg_replace('/\$fluent\s*=\s*\$container->get\(Database::class\);/', '// $fluent removed, use $this->db (ExtendedPdo)', $updated);

    // 3) Peers/Agents aggregation replacement
    $replacementPeers = '$sql = "SELECT agent, LEFT(peer_id, 8) AS peer_id FROM peers GROUP BY agent, LEFT(peer_id, 8)";' . "\n"
                      . '$agents = $this->db->fetchAll($sql);' . "\n";
    $updated = preg_replace(
        '#\$agents\s*=\s*\$fluent->from\(\'peers\'\)\s*->select\(null\)\s*->select\(\'agent\'\)\s*->select\(\'LEFT\(peer_id,\s*8\)\s+AS\s+peer_id\'\)\s*->groupBy\(\'agent\'\)\s*->groupBy\(\'peer_id\'\)\s*->fetchAll\(\);\s*#s',
        $replacementPeers,
        $updated
    );

    // 4) Categories list ordered (iterator or fetchAll)
    $replacementCatsAll = '$sql = "SELECT * FROM categories ORDER BY ordered, id";' . "\n"
                        . '$cats = $this->db->fetchAll($sql);' . "\n";
    $updated = preg_replace(
        '#\$cats\s*=\s*\$fluent->from\(\'categories\'\)\s*->orderBy\(\'ordered\'\)\s*;#s',
        $replacementCatsAll,
        $updated
    );
    $updated = preg_replace(
        '#\$cats\s*=\s*\$fluent->from\(\'categories\'\)\s*->orderBy\(\'ordered\'\)\s*->fetchAll\(\);\s*#s',
        $replacementCatsAll,
        $updated
    );

    // 5) Categories children by parent
    $replacementChildren = '$sql = "SELECT * FROM categories WHERE parent_id = :pid ORDER BY ordered, id";' . "\n"
                         . '$children = $this->db->fetchAll($sql, [\'pid\' => (int) $parentId]);' . "\n";
    $updated = preg_replace(
        '#\$children\s*=\s*\$fluent->from\(\'categories\'\)\s*->where\(\'parent_id\s*=\s*\?\',\s*\$parentId\)\s*->orderBy\(\'ordered\'\)\s*->fetchAll\(\);\s*#s',
        $replacementChildren,
        $updated
    );

    // 6) Delete category by id
    $replacementDelete = '$this->db->perform("DELETE FROM categories WHERE id = :id", ["id" => (int) $params["id"]]);' . "\n";
    $updated = preg_replace(
        '#\$fluent->deleteFrom\(\'categories\'\)\s*->where\(\'id\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->execute\(\);\s*#s',
        $replacementDelete,
        $updated
    );

    // 7) Generic update with $set → leave TODO marker
    $updated = preg_replace(
        '#\$fluent->update\(\'categories\'\)\s*->set\(\$set\)\s*->where\(\'id\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->execute\(\);#s',
        '// TODO(batch39): Replace FluentPDO update with explicit UPDATE using named columns and $this->db->perform().' . "\n",
        $updated
    );

    if ($updated !== $original) {
        file_put_contents($path, $updated);
        $changed++;
        echo "Updated: $path\n";
    }
}

echo "Batch 39 completed. Files changed: $changed\n";
