<?php
declare(strict_types=1);

/**
 * tools/fix-strict-first.php
 *
 * Ensure declare(strict_types=1) is the very first statement in the FIRST PHP block
 * of every admin/*.php. Does NOT flatten the file; preserves later PHP blocks and any HTML.
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
        // unreadable file; skip
        continue;
    }
}
    $orig = $src;
    $scanned++;

    // Strip UTF-8 BOM if present
    if (strncmp($src, "\xEF\xBB\xBF", 3) === 0) {
        $src = substr($src, 3);
    }

    // Locate first "<?php"
    $openPos = strpos($src, '<?php');
    if ($openPos === false) {
        // No PHP block in this file
        continue;
    }

    // Determine end of first PHP block: first "?>" after opener (or EOF)
    $afterOpen = $openPos + 5; // length of "<?php"
    $closePos  = strpos($src, '?>', $afterOpen);
    $blockEnd  = ($closePos === false) ? strlen($src) : $closePos; // exclusive index

    // Split into: prefix | first-block | suffix
    $prefix    = substr($src, 0, $openPos);
    $phpOpen   = '<?php';
    $blockBody = substr($src, $afterOpen, $blockEnd - $afterOpen);
    $suffix    = ($closePos === false) ? '' : substr($src, $closePos); // includes '?>' and rest

    // Remove ANY existing declare(strict_types=1); within the first block
    $blockBodyNoDeclare = preg_replace(
        '/\bdeclare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i',
        '',
        $blockBody
    );
    if ($blockBodyNoDeclare === null) {
        // PCRE failure fallback
        $blockBodyNoDeclare = $blockBody;
    }

    // Trim leading newlines in the first block body (keep comments/code otherwise)
    $blockBodyNoDeclare = preg_replace('/^\R+/', '', $blockBodyNoDeclare);

    // Rebuild first block so declare is the very first statement
    $rebuilt =
        $prefix .
        $phpOpen . "\n" .
        "declare(strict_types=1);\n\n" .
        $blockBodyNoDeclare .
        $suffix;

    if ($rebuilt !== $orig) {
        if (file_put_contents($path, $rebuilt) === false) {
            fwrite(STDERR, "Failed to write: {$path}\n");
            continue;
        }
        $changed++;
    }
}

echo "fix-strict-first: scanned={$scanned}, changed={$changed}\n";
