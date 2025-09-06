<?php
declare(strict_types=1);

/**
 * Force declare(strict_types=1) to be the very first statement (right after <?php)
 * for all files in admin/*.php. Whitespace and comments may appear before declare,
 * but no code (e.g., require_once, use, namespace, etc).
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

    // Normalize BOM and ensure opening tag
    $src = preg_replace('/^\xEF\xBB\xBF/', '', $src);
    if (!str_starts_with($src, "<?php")) {
        $src = "<?php\n" . ltrim($src);
    }

    // Remove any existing declare(strict_types=1); near top
    $src = preg_replace('/<\?php\s+declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i', "<?php\n", $src);

    // Build new header: <?php, declare, blank, then the rest
    $lines = explode("\n", $src);

    // Guarantee first line is exactly "<?php"
    if (trim($lines[0]) !== '<?php') {
        array_unshift($lines, '<?php');
    }

    // Remove empty lines right after opener
    while (isset($lines[1]) && trim($lines[1]) === '') {
        array_splice($lines, 1, 1);
    }

    // Insert declare(strict_types=1); as line 2 (if not already there)
    if (!isset($lines[1]) || stripos($lines[1], 'declare(strict_types=1);') === false) {
        array_splice($lines, 1, 0, 'declare(strict_types=1);');
        array_splice($lines, 2, 0, ''); // blank line for readability
    }

    $new = implode("\n", $lines);
    if ($new !== $orig) {
        file_put_contents($path, $new);
        $changed++;
    }
}

echo "fix-strict-first: scanned={$scanned}, changed={$changed}\n";
