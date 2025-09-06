<?php declare(strict_types=1);
/**
 * Batch 43.1 — Hotfix for admin scripts
 *
 * Goals:
 *  - In non-class PHP files (no "class " present):
 *      * Replace "$db->…" with "$db->…"
 *      * Ensure "$db = $container->get(Database::class);" exists (insert after header if missing)
 *  - Remove "
 *  - Collapse "$x = $y = <expr>;" into "$x = <expr>;" when <expr> starts with "$db->"
 */

$root = getcwd();
$rii  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

$changed = 0;
$scanned = 0;

foreach ($rii as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') continue;

    // Ignore vendor etc.
    $rel = ltrim(str_replace($root, '', $path), '/');
    if (preg_match('~(^|/)(vendor|node_modules|\.git|storage|public/uploads|public/cache|bootstrap/cache)/~', $rel)) {
        continue;
    }

    $src = file_get_contents($path);
    if ($src === false) continue;
    $orig = $src;
    $scanned++;

    $isClassFile = (bool) preg_match('/^\s*class\s+/m', $src);

    // 1) Remove TODO(batch41) comments
    $src = preg_replace('/\s*\/\/\s*TODO\(batch41\):[^\r\n]*/', '', $src);

    if (!$isClassFile) {
        // 2) Replace $db-> with $db-> in non-class scripts
        $src = preg_replace('/\$db->/', '$db->', $src);

        // 3) Ensure $db is defined; if not, insert after header / declare / use block
        if (!preg_match('/\$db\s*=\s*\$container->get\(Database::class\);/', $src)) {
            // Find insertion point:
            // after strict_types, namespace/use, or opening <?php at top.
            $insertLine = '$db = $container->get(Database::class);' . PHP_EOL;
            if (preg_match('/(<\?php\s+declare\(strict_types=1\);\s*)/m', $src, $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1] + strlen($m[0][0]);
                $src = substr($src, 0, $pos) . PHP_EOL . $insertLine . substr($src, $pos);
            } elseif (preg_match('/(<\?php)/', $src, $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1] + strlen($m[0][0]);
                $src = substr($src, 0, $pos) . PHP_EOL . $insertLine . substr($src, $pos);
            } else {
                // fallback: prepend
                $src = "<?php\n" . $insertLine . "?>\n" . $src;
            }
        }

        // 4) Simplify "$x = $y = EXPR;" where EXPR starts with $db->
        //    Example seen: $cat = $db->fetchRow(...)  →  $cat = $db->fetchRow(...)
        $src = preg_replace(
            '/\$(\w+)\s*=\s*\$(\w+)\s*=\s*(\$db->[^\;]+);/',
            '\$$1 = $3;',
            $src
        );
    }

    if ($src !== $orig) {
        file_put_contents($path, $src);
        $changed++;
    }
}

echo "Batch 43.1 hotfix: scanned {$scanned} files, changed {$changed} files.\n";
