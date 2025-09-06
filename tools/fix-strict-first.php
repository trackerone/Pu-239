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
 *   - Remove ALL additional "<?php" occurrences except the first one.
 *   - Remove ALL existing declare(strict_types=1) occurrences.
 *   - Rebuild the file to start with exactly:
 *       <?php
 *       declare(strict_types=1);
 *
 *       <rest of content>
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
    if ($f->isDot() || !$f->isFile()) {
        continue;
    }
    if (strtolower($f->getExtension()) !== 'php') {
        continue;
    }

    $path = $f->getPathname();
    $src  = file_get_contents($path);
    if ($src === false) {
        continue;
    }
    $orig = $src;
    $scanned++;

    // 0) Strip UTF-8 BOM
    $src = preg_replace('/^\xEF\xBB\xBF/', '', $src);

    // 1) Ensure we have an opening tag at the top
    if (!preg_match('/^\s*<\?php/i', $src)) {
        $src = "<?php\n" . ltrim($src);
    }

    // 2) Flatten to a single PHP block: remove all closing tags
    $src = str_replace("?>", "\n", $src);

    // 3) Keep only the first opening tag; remove subsequent ones
    // Normalize the very first one exactly to "<?php\n"
    $src = preg_replace('/^\s*<\?php\s*/i', "<?php\n", $src, 1);
    // Remove any other "<?php" occurrences later in the file
    $src = preg_replace('/<\?php\s*/i', '', $src);

    // 4) Remove any existing declare(strict_types=1);
    $src = preg_replace('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i', '', $src);

    // 5) Remove empty lines right after the (normalized) opener
    $src = preg_replace('/^<\?php\s*\R+/i', "<?php\n", $src, 1);

    // 6) Rebuild with declare() as the very first statement
    // Split into lines so we can ensure an empty line after declare for readability
    $lines = explode("\n", $src);

    // Guarantee the first line is exactly "<?php"
    if (!isset($lines[0]) || trim($lines[0]) !== '<?php') {
        array_unshift($lines, '<?php');
    }

    // Remove any blank lines after the opener
    $idx = 1;
    while (isset($lines[$idx]) && trim($lines[$idx]) === '') {
        array_splice($lines, $idx, 1);
    }

    // Insert declare(strict_types=1); as line 2 if it's not there already
    if (!isset($lines[1]) || stripos($lines[1], 'declare(strict_types=1);') === false) {
        array_splice($lines, 1, 0, 'declare(strict_types=1);');
        // Add a blank line after declare for readability
        array_splice($lines, 2, 0, '');
    }

    $new = implode("\n", $lines);

    if ($new !== $orig) {
        file_put_contents($path, $new);
        $changed++;
    }
}

echo "fix-strict-first: scanned={$scanned}, changed={$changed}\n";
