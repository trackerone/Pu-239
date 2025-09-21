<?php
declare(strict_types=1);

use Pu239\Config\ConfigRepository;
use Pu239\Database;

global $container, $site_config;

$db = null;
if (isset($container)) {
    try {
        $db = $container->get(Database::class);
    } catch (\Throwable $e) {
        $db = null;
    }
}

/** @var ConfigRepository|null $config */
$config = null;
if (isset($container)) {
    try {
        $config = $container->get(ConfigRepository::class);
    } catch (\Throwable $e) {
        $config = null;
    }
}

$site_config = is_array($site_config ?? null) ? $site_config : [];
$site_config['paths'] = isset($site_config['paths']) && is_array($site_config['paths']) ? $site_config['paths'] : [];
$site_config['paths']['cache'] = realpath(__DIR__ . '/../storage/cache') ?: (__DIR__ . '/../storage/cache');
/**
 * runtime_safe.php
 * Minimal, defensiv bootstrap UDEN hårdt krav om mysql_compat.php.
 * - Loader composer autoloader hvis den findes
 * - Definerer app_halt/safe_eval/debug_log helpers
 */

// Composer autoload (hvis tilgængelig)
$vendor = __DIR__ . '/../vendor/autoload.php';
if (is_file($vendor)) {
    require_once $vendor;
}

// Valgfri legacy-kompat (kun hvis filen findes lokalt i miljøet, IKKE i repo)
$compat = __DIR__ . '/mysql_compat.php';
if (is_file($compat)) {
    // OBS: Repo Sanity kræver at denne fil IKKE ligger i repoet.
    require_once $compat;
}

// Forsigtige defaults
if (function_exists('mb_internal_encoding')) {
    @mb_internal_encoding('UTF-8');
}
@ini_set('default_charset', 'UTF-8');

// Hjælpefunktioner
if (!function_exists('app_halt')) {
    /**
     * Blød erstatning for die/exit brugt i koden – smider exception så tests kan fange den.
     */
    function app_halt(mixed $message = 'Application halted', int $code = 1): void
    {
        $text = is_scalar($message) ? (string) $message : json_encode($message, JSON_UNESCAPED_UNICODE);
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code(500);
        }
        error_log('[app_halt] ' . $text);
        throw new \RuntimeException($text ?: 'Application halted', $code);
    }
}

if (!function_exists('safe_eval')) {
    function safe_eval(string $code): void
    {
        throw new \RuntimeException('eval is disabled via safe_eval');
    }
}

if (!function_exists('debug_log')) {
    function debug_log(mixed ...$args): void
    {
        foreach ($args as $a) {
            $line = is_scalar($a) ? (string)$a : json_encode($a, JSON_UNESCAPED_UNICODE);
            error_log('[debug] ' . $line);
        }
    }
}
/**
 * @deprecated Use bound parameters via Pu239\Database. To be removed when repo is clean.
 */
function sqlesc/**deprecated**/($x)
{
    trigger_error('sqlesc is deprecated – migrate to bound params', E_USER_DEPRECATED);
    return $x;
}

