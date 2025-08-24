<?php
declare(strict_types=1);

(function (): void {
    // Composer autoload hvis tilgængelig
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
    }

    // Fallback autoload for "Pu239\" -> src/
    spl_autoload_register(static function (string $class): void {
        $prefix  = 'Pu239\\';
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

    $issues = [];

    if (!class_exists('Pu239\\Database')) {
        $issues[] = 'Class Pu239\\Database not found. Ensure src/Database.php exists and autoload is correct.';
    }

    if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
        $issues[] = 'PDO MySQL driver not available in this runtime.';
    }

    if ($issues) {
        echo "PDO Sanity FAILED\n";
        foreach ($issues as $i) {
            echo "- {$i}\n";
        }
        return;
    }

    echo "PDO Sanity PASSED\n";
})();
