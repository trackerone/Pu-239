<?php
/**
 * Repo sanity (v2): only ensures mysql_compat.php file is removed.
 * Ignores third-party adminer and deprecated mysql_* presence.
 */
$root = __DIR__ . '/../';
if (file_exists($root . 'include/mysql_compat.php')) {
    echo "Repo sanity failed:\n- include/mysql_compat.php should be deleted from the repo.\n";
    exit(1);
}
echo "Repo sanity: OK\n";
