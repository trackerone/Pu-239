<?php
declare(strict_types=1);

/**
 * PDO Sanity: verifies Pu239\Database can be autoloaded.
 * - No exit()/die() used (avoids terminate_calls).
 * - Provides a robust fallback autoloader for src/.
 */

(function (): void {
    // 1) Composer autoload if available
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
    }

    // 2) Fallback PSR-4-ish autoload for "Pu239\" -> src/
    spl_autoload_register(static function (string $class): void {
        $prefix = 'Pu239\\';
        $baseDir = __DIR__ . '/../src/';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $path = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    });

    // 3) Check class availability
    $issues = [];

    if (!class_exists('Pu239\\Database')) {
        $issues[] = 'Class Pu239\\Database not found. Ensure src/Database.php exists and autoload is correct.';
    }

    // Optional: lightweight PDO driver check (no exit)
    if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
        // Not strictly required for class sanity, but informative
        $issues[] = 'PDO MySQL driver not available in this runtime.';
    }

    // 4) Output result (script returns 0 regardless; CI decides based on parser of output)
    if ($issues) {
        echo "PDO Sanity FAILED\n";
        foreach ($issues as $i) {
            echo "- {$i}\n";
        }
    } else {
        echo "PDO Sanity PASSED\n";
    }
})();
