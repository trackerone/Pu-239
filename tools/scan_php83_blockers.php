<?php
/**
 * Scan composer.lock for packages that block PHP 8.3.
 * Soft exit (0) so PRs are never blocked.
 */
declare(strict_types=1);

$lockPath = __DIR__ . '/../composer.lock';
if (!file_exists($lockPath)) {
    echo "composer.lock not found.\n";
    exit(0);
}
$lock = json_decode(file_get_contents($lockPath), true);
if (!$lock) {
    echo "Failed to parse composer.lock\n";
    exit(0);
}

$packages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);
$installed = [];
foreach ($packages as $p) {
    $installed[$p['name']] = $p['version'];
}

$blockers = [
    'biblys/isbn' => 'Requires PHP ^7.1 in 2.x; upgrade to 3.x',
    'envms/fluentpdo' => 'Requires PHP ^7.1; replace with Aura.Sql or PDO',
    'umpirsky/composer-permissions-handler' => 'Requires PHP ^7.2; remove and handle permissions in CI/deploy',
];

echo "== PHP 8.3 blockers (heuristic) ==\n\n";
$foundAny = false;
foreach ($blockers as $name => $note) {
    if (isset($installed[$name])) {
        $foundAny = true;
        echo "- $name @ {$installed[$name]} -> $note\n";
    }
}
if (!$foundAny) {
    echo "No known blockers found.\n";
}

echo "\nTips:\n";
echo "- Set composer.json require.php to ^8.3 and/or config.platform.php to 8.3.0.\n";
echo "- Re-run Batch 32 locked diagnostics to verify.\n";
