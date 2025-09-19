<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap_core.php';
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

if (file_exists(__DIR__ . '/include/app.php')) {
    require_once __DIR__ . '/include/app.php';
}
require_once __DIR__ . '/include/bootstrap_pdo.php';
require_once __DIR__ . '/include/config_compat.php';
