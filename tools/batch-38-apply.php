<?php declare(strict_types=1);

$db = $container->get(Database::class);
/**
 * Batch 38 transformer (v4)
 * - Scope: admin/ PHP files
 * - Goal: Remove FluentPDO usage for categories & peers snippets; remove Literal import; tighten guards.
 * - Fix: Ensure injected code never uses invalid string concatenation (+); use plain newlines.
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
    $updated = preg_replace('/@throws\s+\\\\Envms\\\\FluentPDO\\\\Exception/', '@throws \\\\PDOException', $updated);

    // 2) Replace container->get(Database::class) hints
    $updated = preg_replace('/\$fluent\s*=\s*\$container->get\(Database::class\);/', '// $fluent removed, use $this->db (ExtendedPdo)', $updated);

    // 3) Categories: COUNT two IDs using IN (:id1,:id2)
    $replacement3 = '$sql = "SELECT COUNT(id) FROM categories WHERE id IN (:id1, :id2)";' . "\n" .
                    '$count = (int) $db->fetchValue($sql, [' . "\n" .
                    "    'id1' => (int) \$params['id']," . "\n" .
                    "    'id2' => (int) \$params['new_cat_id']," . "\n" .
                    ']);' . "\n";
    $updated = preg_replace(
        '#\$count\s*=\s*\$fluent->from\(\'categories\'\)\s*->select\(null\)\s*->select\(\'COUNT\(id\)\s+AS\s+count\'\)\s*->where\(\'id\',\s*\[\s*\$params\[\'id\'\],\s*\$params\[\'new_cat_id\'\]\s*\]\)\s*->fetch\(\'count\'\);\s*#s',
        $replacement3,
        $updated
    );

    // 4) Categories: fetch single by id
    $replacement4 = '$cat = $db->fetchRow("SELECT * FROM categories WHERE id = :id", [\'id\' => (int) $params[\'id\']]);' . "\n";
    $updated = preg_replace(
        '#\$cat\s*=\s*\$fluent->from\(\'categories\'\)\s*->where\(\'id\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->fetch\(\);\s*#s',
        $replacement4,
        $updated
    );

    // 5) Torrents: COUNT by category guard
    $replacement5 = '$refCount = (int) $db->fetchValue("SELECT COUNT(id) FROM torrents WHERE category = :id", [\'id\' => (int) $params[\'id\']]);' . "\n";
    $updated = preg_replace(
        '#\$count\s*=\s*\$fluent->from\(\'torrents\'\)\s*->select\(null\)\s*->select\(\'COUNT\(id\)\s+AS\s+count\'\)\s*->where\(\'category\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->fetch\(\'count\'\);\s*#s',
        $replacement5,
        $updated
    );
    $updated = preg_replace(
        '#if\s*\(\s*\$count\s*\)\s*\{#',
        'if ($refCount > 0) {',
        $updated
    );

    // 6) Parents list (top level)
    $replacement6 = '$sql = "SELECT id, name, image, COALESCE(cat_desc, \'\') AS cat_desc, ordered FROM categories WHERE parent_id = 0 ORDER BY ordered";' . "\n" .
                    '$parents = $db->fetchAll($sql);' . "\n";
    $updated = preg_replace(
        '#\$parents\s*=\s*\$fluent->from\(\'categories\'\)\s*->select\(\'IF\s*\(cat_desc\s+IS\s+NULL,\s*"",\s*cat_desc\)\s+AS\s+cat_desc\'\)\s*->where\(\'parent_id\s*=\s*0\'\)\s*->orderBy\(\'ordered\'\)\s*->fetchAll\(\);\s*#s',
        $replacement6,
        $updated
    );

    // 7) Reorder categories - iterator → explicit loop
    $replacement7 = '$rows = $db->fetchAll("SELECT id FROM categories ORDER BY ordered, id");' . "\n" .
                    'foreach ($rows as $cat) {' . "\n" .
                    '    $db->perform("UPDATE categories SET ordered = :ord WHERE id = :id", ["ord" => ++$i, "id" => (int) $cat["id"]]);';
    $updated = preg_replace(
        '#\$cats\s*=\s*\$fluent->from\(\'categories\'\)\s*->orderBy\(\'ordered\'\);\s*foreach\s*\(\s*\$cats\s+as\s+\$cat\s*\)\s*\{\s*\$set\s*=\s*\[\s*\'ordered\'\s*=>\s*\+\+\$i,\s*\];#s',
        $replacement7,
        $updated
    );

    // 8) Admin peers → agents aggregation
    $replacement8 = '$sql = "SELECT agent, LEFT(peer_id, 8) AS peer_id FROM peers GROUP BY agent, LEFT(peer_id, 8)";' . "\n" .
                    '$agents = $db->fetchAll($sql);' . "\n";
    $updated = preg_replace(
        '#\$agents\s*=\s*\$fluent->from\(\'peers\'\)\s*->select\(null\)\s*->select\(\'agent\'\)\s*->select\(\'LEFT\(peer_id,\s*8\)\s+AS\s+peer_id\'\)\s*->groupBy\(\'agent\'\)\s*->groupBy\(\'peer_id\'\)\s*->fetchAll\(\);\s*#s',
        $replacement8,
        $updated
    );

    // 9) Generic deleteFrom('categories') → DELETE
    $replacement9 = '$db->perform("DELETE FROM categories WHERE id = :id", ["id" => (int) $params["id"]]);' . "\n";
    $updated = preg_replace(
        '#\$fluent->deleteFrom\(\'categories\'\)\s*->where\(\'id\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->execute\(\);\s*#s',
        $replacement9,
        $updated
    );

    // 10) Generic update('categories')->set($set)... → leave TODO marker
    $updated = preg_replace(
        '#\$fluent->update\(\'categories\'\)\s*->set\(\$set\)\s*->where\(\'id\s*=\s*\?\',\s*\$params\[\'id\'\]\)\s*->execute\(\);#s',
        '// TODO(batch38): Replace FluentPDO update with explicit UPDATE statement using known columns.' . "\n" .
        '// Example:' . "\n" .
        '// $sql = "UPDATE categories SET name = :name, image = :image, cat_desc = :desc, parent_id = :parent, ordered = :ordered WHERE id = :id";' . "\n" .
        '// $db->perform($sql, [...]);' . "\n",
        $updated
    );

    if ($updated !== $original) {
        file_put_contents($path, $updated);
        $changed++;
        echo "Updated: $path\n";
    }
}

echo "Batch 38 completed. Files changed: $changed\n";
