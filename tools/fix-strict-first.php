<?php
declare(strict_types=1);

/**
 * tools/fix-strict-first.php
 *
 * Ensure declare(strict_types=1) is the very first statement in the FIRST PHP block
 * of every admin/*.php. Do NOT flatten the file; preserve later PHP blocks and any HTML.
 */

$root = getcwd();
$dir  = $root . '/admin';

if (!is_dir($dir)) {
    fwrite(STDERR, "admin/ directory not found\n");
    exit(0);
}

$scanned = 0;
$changed = 0;

$iterator = new DirectoryIterator($dir);
foreach ($iterator as $entry) {
    if ($entry->isDot() || !$entry->isFile()) {
        continue;
    }
    if (strtolower($entry->getExtension()) !== 'php') {
        continue;
    }

    $path = $entry->getPathname();
    $src  = file_get_contents($path);
    if ($src === false) {
        continue;
    }
    $orig = $src;
    $scanned++;

    // Strip UTF-8 BOM
    if (strncmp($src, "\xEF\xBB\xBF", 3) === 0) {
        $src = substr($src, 3);
    }

    // Find first "<?php"
    $openPos = stripos($src, '<?php');
    if ($openPos === false) {
        // No PHP block
        continue;
    }

    // Find end of first PHP block: first "?>" after opener (or EOF)
    $afterOpen = $openPos + 5; // length of "<?php"
    $closePos  = strpos($src, '?>', $afterOpen);
    $blockEnd  = ($closePos === false) ? strlen($src) : $closePos; // exclusive index

    // Split file into: prefix | first-block | suffix
    $prefix    = substr($src, 0, $openPos);
    $phpOpen   = '<?php';
    $blockBody = substr($src, $afterOpen, $blockEnd - $afterOpen);
    $suffix    = ($closePos === false) ? '' : substr($src, $closePos); // includes '?>' and rest

    // Remove any existing declare(strict_types=1); in the first block
    $blockBodyNoDeclare = preg_replace(
        '/\bdeclare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i',
        '',
        $blockBody
    );

    // Trim leading newlines in the first block
    $blockBodyNoDeclare = ltrim($blockBodyNoDeclare, "\r\n");

    // Rebuild first block so declare is the very first statement
    $rebuilt =
        $prefix .
        $phpOpen . "\n" .
        "declare(strict_types=1);\n\n" .
        $blockBodyNoDeclare .
        $suffix;

    if ($rebuilt !== $orig) {
        $ok = file_put_contents($path, $rebuilt);
        if ($ok === false) {
            fwrite(STDERR, "Failed to write: {$path}\n");
            continue;
        }
        $changed++;
    }
}

echo "fix-strict-first: scanned={$scanned}, changed={$changed}\n";
