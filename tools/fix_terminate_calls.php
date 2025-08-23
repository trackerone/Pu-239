<?php
/**
 * tools/fix_terminate_calls.php
 * Replaces die()/exit() with app_halt() for specific files.
 * Does NOT use exit; prints status and returns.
 */
$root = realpath(__DIR__ . '/../');
$targets = [
    'chat/lib/class/AJAXChat.php',
    'public/rss.php',
];
$re_empty = '/\b(?:exit|die)\s*\(\s*\)\s*;/i';
$re_arg   = '/\b(?:exit|die)\s*\((.*?)\)\s*;/is';
$re_bare  = '/^\s*(?:exit|die)\s*;\s*$/im';

$changed = 0;
foreach ($targets as $rel) {
    $path = $root . '/' . $rel;
    if (!is_file($path)) continue;
    $src = file_get_contents($path);
    if ($src === false) continue;
    $new = preg_replace($re_empty, "app_halt('Exit called');", $src);
    $new = preg_replace($re_arg, "app_halt($1);", $new);
    $new = preg_replace($re_bare, "app_halt('Exit called');", $new);
    if ($new !== $src) {
        // Ensure runtime_safe require after opening tag
        if (strpos($new, 'runtime_safe.php') === false) {
            if (preg_match('/<\?php/',$new,$m,PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1] + strlen($m[0][0]);
                $new = substr($new,0,$pos) . "\nrequire_once __DIR__ . '/../include/runtime_safe.php';\n" . substr($new,$pos);
            }
        }
        file_put_contents($path, $new);
        echo "Terminate calls fixed: {$rel}\n";
        $changed++;
    }
}
if ($changed === 0) echo "No terminate() calls found.\n";
