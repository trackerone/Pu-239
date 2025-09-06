<?php
$db = $container->get(Database::class);

/**
 * tools/check_platform.php
 * Soft validator for PHP 8.3 platform alignment.
 *
 * - Reads composer.json
 * - Prints current PHP constraints from "require.php" and "config.platform.php" (if any)
 * - Provides a simple verdict and suggestions
 * - Exits with 0 (soft) to avoid blocking PRs
 */

declare(strict_types=1);

function readJson(string $path): array {
    if (!file_exists($path)) {
        echo "composer.json not found at: $path\n";
        return [];
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        echo "Failed to parse composer.json\n";
        return [];
    }
    return $data;
}

$composer = readJson('composer.json');
$requirePhp = $composer['require']['php'] ?? null;
$configPlatformPhp = $composer['config']['platform']['php'] ?? null;

echo "=== Composer PHP Platform Alignment Report ===\n\n";
echo "Detected constraints:\n";
echo " - require.php: " . ($requirePhp ?? '(not set)') . "\n";
echo " - config.platform.php: " . ($configPlatformPhp ?? '(not set)') . "\n\n";

/**
 * Very simple heuristic: if require.php or platform.php clearly targets 8.3 or 8.x ranges that include 8.3.
 * This is not a full semver solver; the authoritative check is 'composer why-not php ^8.3' run in CI.
 */
function allowsPhp83(?string $constraint): bool {
    if ($constraint === null) return false;
    $c = str_replace(' ', '', strtolower($constraint));

    // Quick passes that typically include 8.3
    $passes = [
        '^8.3', '>=8.3', '~8.3', '8.3.*', '^8', '>=8', '>=8.0', '>=8.1', '>=8.2'
    ];
    foreach ($passes as $p) {
        if (strpos($c, $p) !== false) {
            return true;
        }
    }
    // Common multi-constraints like "^8.1|^8.2|^8.3"
    if (preg_match('/8\.3/', $c)) return true;

    return false;
}

$allowByRequire = allowsPhp83($requirePhp);
$allowByPlatform = allowsPhp83($configPlatformPhp);

$ok = $allowByRequire || $allowByPlatform;

echo "Verdict: " . ($ok ? "✅ Looks compatible with PHP 8.3 by constraints" : "❌ Constraints do not clearly allow PHP 8.3") . "\n\n";

if (!$ok) {
    echo "Suggestions:\n";
    echo "  - In composer.json, set one of:\n";
    echo '    * "require": { "php": "^8.3" }' . "\n";
    echo '    * or "config": { "platform": { "php": "8.3.0" } }' . "\n";
    echo "  - Then run in CI or locally: composer validate && composer why-not php ^8.3\n";
    echo "  - If specific packages block 8.3, upgrade or replace them.\n";
}

exit(0); // always soft exit
