<?php
/**
 * Repo sanity check — fails CI if:
 *  - include/mysql_compat.php still exists
 *  - deprecated mysql_* functions appear anywhere
 */
$root = __DIR__ . '/../';

$errors = [];

if (file_exists($root . 'include/mysql_compat.php')) {
    $errors[] = 'include/mysql_compat.php should be deleted from the repo.';
}

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (!preg_match('/\.php$/i', $path)) continue;
    $rel = substr($path, strlen($root));
    $content = @file_get_contents($path);
    if ($content === false) continue;
    if (preg_match('/\bmysql_(query|connect|pconnect|select_db|fetch_(assoc|array|row)|num_rows|real_escape_string|insert_id|error|errno)\b/i', $content)) {
        $errors[] = "Deprecated mysql_* usage in: {$rel}";
    }
}

if ($errors) {
    echo "Repo sanity failed:\n";
    foreach ($errors as $e) echo "- {$e}\n";
    exit(1);
}
echo "Repo sanity: OK\n";
