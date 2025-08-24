<?php
declare(strict_types=1);
/**
 * tools/pdo_sanity.php
 * CI-smoke test for PDO bootstrap – uden exit()/die() og med PSR‑4 fallback.
 */

$root = __DIR__ . '/../';
$errors = [];

/** PSR-4 autoload fallback for Pu239\ (virker selv uden composer.json) */
spl_autoload_register(function ($class) use ($root) {
    $prefix = 'Pu239\\';
    $len = strlen($prefix);
    if (strncmp($class, $prefix, $len) !== 0) return;
    $rel = substr($class, $len);
    $file = $root . 'src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// Composer autoload (hvis findes)
$autoload = $root . 'vendor/autoload.php';
if (is_file($autoload)) require_once $autoload;

// runtime_safe + bootstrap
$runtime   = $root . 'include/runtime_safe.php';
$bootstrap = $root . 'include/bootstrap_pdo.php';
if (!is_file($runtime))   { $errors[] = 'Missing include/runtime_safe.php'; }   else { require_once $runtime; }
if (!is_file($bootstrap)) { $errors[] = 'Missing include/bootstrap_pdo.php'; } else { require_once $bootstrap; }

// autoload test
if (!class_exists(\Pu239\Database::class))  { $errors[] = 'Class Pu239\\Database not found (autoload missing?).'; }
if (!function_exists('db'))  { $errors[] = 'Function db() missing (bootstrap_pdo.php)'; }
if (!function_exists('pdo')) { $errors[] = 'Function pdo() missing (bootstrap_pdo.php)'; }

// Valgfri DB‑roundtrip hvis config/database.php findes
$configFile = $root . 'config/database.php';
if (empty($errors) && is_file($configFile)) {
    try {
        $dbh  = pdo();
        $stmt = $dbh->prepare('SELECT 1');
        $stmt->execute();
        $val = $stmt->fetchColumn();
        if ((string)$val !== '1') $errors[] = 'Unexpected result from SELECT 1';
    } catch (Throwable $e) {
        $errors[] = 'PDO round-trip failed: ' . $e->getMessage();
    }
}

if ($errors) {
    echo "PDO Sanity FAILED\n";
    foreach ($errors as $e) echo "- {$e}\n";
    return 1; // ← ingen terminate-kald
}
echo "PDO Sanity OK\n";
return 0;
