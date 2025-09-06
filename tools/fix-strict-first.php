<?php
declare(strict_types=1);

/**
 * tools/fix-strict-first.php
 *
 * Minimal: insert "declare(strict_types=1);" immediately after the FIRST "<?php"
 * in every admin/*.php, if not already present right there. Do NOT touch later blocks/HTML.
 */

$dir = __DIR__ . '/../admin';
if (!is_dir($dir)) {
    fwrite(STDERR, "admin/ directory not found\n");
    exit(0);
}

$files = glob($dir . '/*.php');
if (!is_array($files)) {
    $files = [];
}

$scanned = 0;
$changed = 0;

foreach ($files as $path) {
    $src = file_get_contents($path);
    if ($src === false) {
        continue;
    }
    $scanned++;

    // Strip BOM if present
    if (strncmp($src, "\xEF\xBB\xBF", 3) === 0) {
        $src = substr($src, 3);
    }

    // Find first "<?php"
    $openPos = strpos($src, '<?php');
    if ($openPos === false) {
        continue; // no PHP block
    }

    $afterOpen = $openPos + 5; // length of "<?php"
    // Look right after opener: if there's already a declare there, skip
    $tail = substr($src, $afterOpen);
    if (preg_match('/^\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $tail)) {
        continue; // already has declare immediately after opener
    }

    // Insert declare right after opener, preserving everything else
    $before = substr($src, 0, $afterOpen);
    $after  = $tail;
    $new    = $before . "\n" . "declare(strict_types=1);\n\n" . $after;

    if ($new !== $src) {
        if (file_put_contents($path, $new) === false) {
            fwrite(STDERR, "Failed to write: {$path}\n");
            continue;
        }
        $changed++;
    }
}

echo "fix-strict-first: scanned={$scanned}, changed={$changed}\n";
