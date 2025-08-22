<?php
require_once __DIR__ . '/runtime_safe.php';
require_once __DIR__ . '/mysql_compat.php';

if (!function_exists('debug_log')) {
    function debug_log($value) {
        $debug = getenv('APP_DEBUG');
        if ($debug && strtolower($debug) !== 'false' && $debug !== '0') {
            if (is_array($value) || is_object($value)) error_log('[DEBUG] ' . debug_log($value, true));
            else error_log('[DEBUG] ' . var_export($value, true));
        }
    }
}
if (!function_exists('app_halt')) {
    function app_halt($message = 'Application halted') {
        if (is_array($message) || is_object($message)) $message = debug_log($message, true);
        error_log('[HALT] ' . (string)$message);
        throw new \RuntimeException((string)$message);
    }
}
if (!function_exists('safe_eval')) {
    function safe_eval($code) {
        error_log('[BLOCKED eval] Attempted to eval:');
        $preview = is_string($code) ? substr($code, 0, 120) : '[non-string]';
        error_log($preview);
        return null;
    }
}
