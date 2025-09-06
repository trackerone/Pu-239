<?php
declare(strict_types=1);

/**
 * Ensure declare(strict_types=1) is the very first statement in the FIRST PHP block
 * of every admin/*.php. Do NOT flatten the file; preserve later PHP blocks and any HTML.
 *
 * Strategy (per file):
 *  - Strip UTF-8 BOM.
 *  - Find first "<?php". If none, skip file.
 *  - Identify end of the first PHP block (first "?>", or end-of-file if none).
 *  - Inside that first block:
 *      * Remove any existing declare(strict_types=1);
 *      * Rebuild the block so it starts with:
 *          <?php
 *          declare(strict_types=1);
 *
 *          <rest of original first-block code (minus old declare)>
 *  - Keep prefix (before first "<?php") and suffix (after end of first block) unchanged.
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
    if (strncmp($src, "\xEF\xBB\xBF", 3) === 0) {
        $src = substr($src, 3);
    }

    // 1) Find first opening tag
    $openPos = stripos($src, '<?php');
    if ($openPos === false) {
        // No PHP block — nothing to do
        continue;
    }

    // 2) Find end of first block (first "?>" after opener)
    $afterOpen = $openPos + 5; // length of "<?php"
    $closePos  = strpos($src, '?>', $afterOpen);
    $blockEnd  = ($closePos === false) ? strlen($src) : $closePos; // exclusive

    // Split into parts
    $prefix    = substr($src, 0, $openPos);
    $phpOpen   = '<?php';
    $blockBody = substr($src, $afterOpen, $blockEnd - $afterOpen);
    $suffix    = ($closePos === false) ? '' : substr($src, $closePos); // includes '?>' and the rest

    // Normalize block body newlines
    // Remove any existing declare(strict_types=1); in the first block
    $blockBodyNoDeclare = preg_replace(
        '/\bdeclare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i',
        '',
        $blockBody
    );

    // Trim leading blank lines in the block (whitespace/comments before declare are allowed by PHP,
    // but we keep it clean and deterministic)
    $blockBodyNoDeclare = ltrim($blockBodyNoDeclare, "\r\n");

    // Rebuild first block: opener + declare + blank line + (rest of block as-is)
    $rebuilt =
        $prefix .
        $phpOpen . "\n" .
        "declare(strict_types=1);\n\n" .
        $blockBodyNoDeclare .
        $suffix;

    if ($rebuilt !== $orig) {
        file_put_contents($path, $rebuilt);
        $changed++;
    }
}

echo "fix-strict-first: scanned={$scanned}, changed={$changed}\n";
