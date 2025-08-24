<?php
declare(strict_types=1);
/**
 * tools/pdo_sanity.php
 * Non-invasive CI smoke test for PDO bootstrap.
 */
$root = __DIR__ . '/../';
$errors = [];

// runtime_safe + bootstrap
$runtime = $root . 'include/runtime_safe.php';
$bootstrap = $root . 'include/bootstrap_pdo.php';
if (!is_file($runtime)) { $errors[] = 'Missing include/runtime_safe.php'; } else { require_once $runtime; }
if (!is_file($bootstrap)) { $errors[] = 'Missing include/bootstrap_pdo.php'; } else { require_once $bootstrap; }

// autoload class
if (!class_exists('\Pu239\Database')) {
    $autoload = $root . 'vendor/autoload.php';
    if (is_file($autoload)) { require_once $autoload; }
}
if (!class_exists('\Pu239\Database')) {
    $errors[] = 'Class Pu239\\Database not found. Add PSR-4 autoload ("Pu239\\": "src/") and composer dump-autoload.';
}

// helpers exist
if (!function_exists('db')) $errors[] = 'Function db() missing (bootstrap_pdo.php)';
if (!function_exists('pdo')) $errors[] = 'Function pdo() missing (bootstrap_pdo.php)';

// optional round-trip if config/db exists
$configFile = $root . 'config/database.php';
if (empty($errors) && is_file($configFile)) {
    try {
        $dbh = pdo();
        $stmt = $dbh->prepare('SELECT 1');
        $stmt->execute();
        $val = $stmt->fetchColumn();
        if ((string)$val !== '1') { $errors[] = 'Unexpected result from SELECT 1'; }
    } catch (Throwable $e) {
        $errors[] = 'PDO round-trip failed: ' . $e->getMessage();
    }
}

if ($errors) { echo "PDO Sanity FAILED\n"; foreach ($errors as $e) echo "- {$e}\n"; exit(1); }
echo "PDO Sanity OK\n";
