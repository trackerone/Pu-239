<?php
declare(strict_types=1);

/**
 * tools/fix-strict-first.php
 *
 * Goal:
 *   Ensure declare(strict_types=1) is the very first statement in the FIRST PHP block
 *   of every admin/*.php. Do NOT flatten the file; preserve later PHP blocks and any HTML.
 *
 * Strategy per file:
 *   1) Strip UTF-8 BOM (if present).
 *   2) Find the first "<?php". If none, skip file.
 *   3) Find the end of that first PHP block (first "?>", or EOF if none).
 *   4) Inside that first block only:
 *      - remove any existing declare(strict_types=1);
 *      - rebuild the first block so it starts with:
 *            <?php
 *            declare(strict_types=1);
 *
 *            <rest of original first-block code (minus old declare)>
 *   5) Keep everything before/after that block unchanged.
 */

$root = getcwd();
$dir  = $root . '/admin';

if (!is_dir($dir)) {
    fwrite(STDERR, "admin/ directory not found\n");
    exit(0);
}

$scanned = 0;
$changed = 0;

try {
    $it = new DirectoryIterator($dir);

    foreach ($it as $entry) {
        if ($entry->isDot() || !$entry->isFile()) {
            continue;
        }
        if (strtolower($entry->getExtension()) !== 'php') {
            continue;
        }

        $path = $entry->getPathname();
        $src  = file_get_contents($path);
        if ($src === false) {
            // Unable to read this file; skip
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

        // 2) Find end of first PHP block
        $afterOpen = $openPos + 5; // length of "<?php"
        $closePos  = strpos($src, '?>', $afterOpen);
        $blockEnd  = ($closePos === false) ? strlen($src) : $closePos; // exclusive index

        // 3) Split file into prefix / first-block / suffix
        $prefix    = substr($src, 0, $openPos);
        $phpOpen   = '<?php';
        $blockBody = substr($src, $afterOpen, $blockEnd - $afterOpen);
        $suffix    = ($closePos === false) ? '' : substr($src, $closePos); // includes '?>' + rest

        // 4) Remove any existing declare(strict_types=1); inside the first block
        $blockBodyNoDeclare = preg_replace(
            '/\bdeclare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/i',
            '',
            $blockBody
        );

        // 5) Trim leading newlines/whitespace in the first block
        $blockBodyNoDeclare = ltrim($blockBodyNoDeclare, "\r\n");

        // 6) Rebuild: opener + declare + blank line + rest of block + suffix
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
} catch (Throwable $e) {
    fwrite(STDERR, "fix-strict-first failed: " . $e->getMessage() . "\n");
    exit(1);
}

echo "fix-strict-first: scanned={$scanned}, changed={$changed}\n";
