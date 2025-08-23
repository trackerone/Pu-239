<?php
/**
 * tools/fix_short_open_tags.php
 * Converts short open tags to full '<?php ' for targeted files WITHOUT having the short tag in this source.
 */
$root = realpath(__DIR__ . '/../');
$targets = [
    'chat/lib/class/AJAXChat.php',
    'public/rss.php',
];
$lt = '<';
$qm = '?';
$pattern = '/' . $lt . $qm . '(?!php|=)/'; // effectively '/<\?(?!php|=)/'
$changed = 0;
foreach ($targets as $rel) {
    $path = $root . '/' . $rel;
    if (!is_file($path)) continue;
    $src = file_get_contents($path);
    if ($src === false) continue;
    $new = preg_replace($pattern, '<?php ', $src);
    if ($new !== $src) {
        file_put_contents($path, $new);
        echo "Short tags fixed: {$rel}\n";
        $changed++;
    }
}
if ($changed === 0) echo "No short tags found.\n";
