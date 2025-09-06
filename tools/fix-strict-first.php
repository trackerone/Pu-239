<?php
declare(strict_types=1);

/**
 * Force declare(strict_types=1) to be the very first statement in every admin/*.php,
 * robustly handling files that:
 *   - start with BOM,
 *   - contain multiple PHP open/close tags,
 *   - have any code before declare.
 *
 * Strategy:
 *   - Strip BOM.
 *   - Replace ALL "?>" with a newline (flatten to a single PHP block).
 *   - Remove ALL additional "<?php" occurrences except the very first one.
 *   - Remove ALL existing declare(strict_types=1) occurrences.
 *   - Rebuild the file to start with:
 *       <?php
 *       declare(strict_types=1);
 *       <blank line>
 *       <rest of content (without leading <?php or declare)>
 */
$root = getcwd();
$dir  = $root . '/admin';

if (!is_dir($dir)) {
    fwrite(STDERR, "admin/ directory not found\n");
    exit(0);
}

$scanned = 0;
$changed = 0;

$it = new DirectoryIterator($dir);
foreach ($it as $f) {
    if ($f->isDot() || !$f->isFile()) continue;
    if (strtolower($f->getExtension()) !== 'php') continue;

    $path = $f->getPathname();
    $src  = file_get_contents($path);
    if ($src === false) continue;
    $orig = $src;
    $scanned++;

    // 0) Strip BOM
    $src = preg_replace('/^\xEF\xBB\xBF/', '', $src);

    // 1) If file has any text before the first "<?php", prepend opener
    if (!preg_match('/^\s*<\?php/i', $src)) {
        $src = "<?php\n" . ltrim($src);
    }

    // 2) Remove ALL closing tags "?>" (we keep a single PHP block)
    $src = str_replace("?>", "\n", $src);

    // 3) Remove ALL additional open tags beyond the first
    //    Keep the first "<?php" only
    $src = preg_replace('/^\s*<\?php/i', "<?php", $src, 1); // normalize first
    $src = preg_replace('/<\?php/i', '', $src);             // strip any subsequent ones

    // 4) Remove ALL existing declare(strict_types=1);
    $src = preg_replace('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i', '', $src);

    // 5) Remove any accidental blank lines right at the start (after we cleaned up)
    $src = preg_replace('/^<\?php\s*\R+/i', "<?php\n", $src, 1);

    // 6) Rebuild with declare strictly first
    $src = "<?php\n" . "declare(strict_types=1);\n\n" . ltrim(substr($src, strlen("<?php\n")));

    // 7) Sanity: ensure we still start correctly
    if (!str_starts_with($src, "<?php\ndeclare(strict_types=1);\n")) {
        $src = "<?php\ndeclare(strict_types=1);\n\n" . ltrim($src);
    }

    if ($src !== $orig) {
        file_put_contents($path, $src);
        $changed++;
    }
}

echo "fix-strict-first: scanned={$scanned}, changed={$changed}\n";
