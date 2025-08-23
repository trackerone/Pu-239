<?php
/**
 * fix_short_open_tags.php
 * Replaces short open tags '<?' (but not '<?=') with '<?php ' in a set of files.
 * Excludes: admin/adminer.php, vendor directory.
 */
$root = realpath(__DIR__ . '/../');
$targets = [
    'chat/lib/class/AJAXChat.php',
    'public/rss.php',
];

$changed = 0;
foreach ($targets as $rel) {
    $path = $root . '/' . $rel;
    if (!file_exists($path)) continue;
    $src = file_get_contents($path);
    if ($src === false) continue;
    $new = preg_replace('/<\?(?!php|=)/', '<?php ', $src);
    if ($new !== $src) {
        file_put_contents($path, $new);
        echo "Short tags fixed: {$rel}\n";
        $changed++;
    }
}

if ($changed === 0) {
    echo "No short tags found in target files.\n";
}
