<?php
/**
 * Static guard: fails CI if risky patterns exist.
 * Run locally (optional): php tools/static_guard.php
 */
$root = __DIR__ . '/../';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$errors = [];
$patterns = [
    'merge_conflict_marker' => '/^(<<<<<<<|=======|>>>>>>>)/m',
    'short_open_tag'        => '/<\?(?!php|=)/',
    'eval_usage'            => '/\beval\s*\(/i',
    'terminate_calls'       => '/\b(die|exit)\s*\(/i',
    'debug_calls'           => '/\b(var_dump|print_r|dd)\s*\(/i',
    'deprecated_mysql'      => '/\bmysql_(query|connect|pconnect|select_db|fetch_(assoc|array|row)|num_rows|real_escape_string|insert_id|error|errno)\b/i',
];

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (!preg_match('/\.php$/i', $path)) continue;

    $rel = substr($path, strlen($root));
    $content = @file_get_contents($path);
    if ($content === false) continue;

    foreach ($patterns as $name => $regex) {
        if (preg_match($regex, $content)) {
            $errors[] = [$name, $rel];
        }
    }
}

if (!empty($errors)) {
    echo "Static guard found issues:\n";
    foreach ($errors as $e) {
        echo "- {$e[0]} : {$e[1]}\n";
    }
    exit(1);
}

echo "Static guard: OK\n";
