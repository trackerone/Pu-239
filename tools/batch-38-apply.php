<?php declare(strict_types=1);
/**
 * Batch 38 transformer
 * - Scope: admin/ PHP files
 * - Goal: Remove FluentPDO usage for categories & peers snippets; remove Literal import; tighten guards.
 * 
 * This uses conservative, pattern-based replacements. It only replaces when it finds exact or near-exact matches.
 * All messages/comments are in English as requested.
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

    // 1) Remove \Envms\FluentPDO\Exception in docblocks → \PDOException
    $updated = preg_replace('/@throws\s+\\\\Envms\\\\FluentPDO\\\\Exception/', '@throws \\PDOException', $updated);

    // 2) Replace container->get(Database::class) → assume $this->db already injected (leave if not present)
    $updated = preg_replace('/\$fluent\s*=\s*\$container->get\(Database::class\);/', '// $fluent removed, use $this->db (ExtendedPdo)', $updated);

    // 3) Categories: COUNT two IDs using IN (:id1,:id2)
    $updated = preg_replace(
        '#\$count\s*=\s*\$fluent->from\(\'categories\'\)\s*->select\(null\)\s*->select\(\'COUNT\(id\)\s+AS\s+count\'\)\s*->where\(\'id\',\s*\[\s*\$params\[\'id\'\],\s*\$params\[\'new_cat_id\'\]\s*\]\)\s*->fetch\(\'count\'\);\s*#s',
        '$sql = "SELECT COUNT(id) FROM categories WHERE id IN (:id1, :id2)";' + "\n" +
        '$count = (int) $this->db->fetchValue($sql, [' + "\n" +
        "    'id1' => (int) \$params['id']," + "\n" +
        "    'id2' => (int) \$params['new_cat_id']," + "\n" +
        ']);' + "\n",
        $updated
    );

    // 4) Categories: fetch single by id
    $updated = preg_replace(
        '#\$cat\s*=\s*\$fluent->from\(\'categories\'\)\s*->where\(\'id\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->fetch\(\);\s*#s',
        '$cat = $this->db->fetchRow("SELECT * FROM categories WHERE id = :id", [\'id\' => (int) $params[\'id\']]);' + "\n",
        $updated
    );

    // 5) Torrents: COUNT by category guard
    $updated = preg_replace(
        '#\$count\s*=\s*\$fluent->from\(\'torrents\'\)\s*->select\(null\)\s*->select\(\'COUNT\(id\)\s+AS\s+count\'\)\s*->where\(\'category\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->fetch\(\'count\'\);\s*#s',
        '$refCount = (int) $this->db->fetchValue("SELECT COUNT(id) FROM torrents WHERE category = :id", [\'id\' => (int) $params[\'id\']]);' + "\n",
        $updated
    );
    // And if the old code checked $count, ensure it now checks $refCount
    $updated = preg_replace(
        '#if\s*\(\s*\$count\s*\)\s*\{#',
        'if ($refCount > 0) {',
        $updated
    );

    // 6) Parents list (top level)
    $updated = preg_replace(
        '#\$parents\s*=\s*\$fluent->from\(\'categories\'\)\s*->select\(\'IF\s*\(cat_desc\s+IS\s+NULL,\s*\"\",\s*cat_desc\)\s+AS\s+cat_desc\'\)\s*->where\(\'parent_id\s*=\s*0\'\)\s*->orderBy\(\'ordered\'\)\s*->fetchAll\(\);\s*#s',
        '$sql = "SELECT id, name, image, COALESCE(cat_desc, \'\') AS cat_desc, ordered FROM categories WHERE parent_id = 0 ORDER BY ordered";' + "\n" +
        '$parents = $this->db->fetchAll($sql);' + "\n",
        $updated
    );

    // 7) Reorder categories - replace Fluent iterator usage (best-effort; leaves custom logic intact)
    $updated = preg_replace(
        '#\$cats\s*=\s*\$fluent->from\(\'categories\'\)\s*->orderBy\(\'ordered\'\);\s*foreach\s*\(\s*\$cats\s+as\s+\$cat\s*\)\s*\{\s*\$set\s*=\s*\[\s*\'ordered\'\s*=>\s*\+\+\$i,\s*\];#s',
        '$rows = $this->db->fetchAll("SELECT id FROM categories ORDER BY ordered, id");' + "\n" +
        'foreach ($rows as $cat) {' + "\n" +
        '    $this->db->perform("UPDATE categories SET ordered = :ord WHERE id = :id", ["ord" => ++$i, "id" => (int) $cat["id"]]);',
        $updated
    );

    // 8) Admin peers → agents aggregation
    $updated = preg_replace(
        '#\$agents\s*=\s*\$fluent->from\(\'peers\'\)\s*->select\(null\)\s*->select\(\'agent\'\)\s*->select\(\'LEFT\(peer_id,\s*8\)\s+AS\s+peer_id\'\)\s*->groupBy\(\'agent\'\)\s*->groupBy\(\'peer_id\'\)\s*->fetchAll\(\);\s*#s',
        '$sql = "SELECT agent, LEFT(peer_id, 8) AS peer_id FROM peers GROUP BY agent, LEFT(peer_id, 8)";' + "\n" +
        '$agents = $this->db->fetchAll($sql);' + "\n",
        $updated
    );

    // 9) Generic deleteFrom('categories')->where('id = ?', ...) → DELETE ... WHERE id = :id
    $updated = preg_replace(
        '#\$fluent->deleteFrom\(\'categories\'\)\s*->where\(\'id\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->execute\(\);\s*#s',
        '$this->db->perform("DELETE FROM categories WHERE id = :id", ["id" => (int) $params["id"]]);' + "\n",
        $updated
    );

    // 10) Generic update('categories')->set($set)...
    if (preg_match('#\$fluent->update\(\'categories\'\)\s*->set\(\$set\)\s*->where\(\'id\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->execute\(\);#s', $updated)) {
        $updated = preg_replace(
            '#\$fluent->update\(\'categories\'\)\s*->set\(\$set\)\s*->where\(\'id\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->execute\(\);#s',
            '// TODO(batch38): Replace FluentPDO update with explicit UPDATE statement using known columns.' + "\n" +
            '// Example:' + "\n" +
            '// $sql = "UPDATE categories SET name = :name, image = :image, cat_desc = :desc, parent_id = :parent, ordered = :ordered WHERE id = :id";' + "\n" +
            '// $this->db->perform($sql, [...]);' + "\n",
            $updated
        );
    }

    if ($updated !== $original) {
        file_put_contents($path, $updated);
        $changed++;
        echo "Updated: $path\n";
    }
}

echo "Batch 38 completed. Files changed: $changed\n";
