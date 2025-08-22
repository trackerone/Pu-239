<?php
/**
 * Runtime safety shims injected by cleanup step.
 * Controlled by environment variables:
 *   APP_DEBUG=true  -> debug_log() writes payloads
 *   APP_ENV=prod    -> suppresses risky behaviour
 */

if (!function_exists('debug_log')) {
    function debug_log($value) {
        $debug = getenv('APP_DEBUG');
        if ($debug && strtolower($debug) !== 'false' && $debug !== '0') {
            // Log to PHP error_log to avoid file permission issues
            if (is_array($value) || is_object($value)) {
                error_log('[DEBUG] ' . print_r($value, true));
            } else {
                error_log('[DEBUG] ' . var_export($value, true));
            }
        }
    }
}

if (!function_exists('app_halt')) {
    function app_halt($message = 'Application halted') {
        // Centralized termination to avoid raw die()/exit()
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }
        error_log('[HALT] ' . (string)$message);
        // Throwing allows global handlers to respond (HTTP 500 etc.)
        throw new \RuntimeException((string)$message);
    }
}

if (!function_exists('safe_eval')) {
    function safe_eval($code) {
        // Prevents execution of arbitrary code. Returns null and logs.
        error_log('[BLOCKED eval] Attempted to eval:');
        // Only log a shortened preview to reduce risk of secrets in logs
        $preview = is_string($code) ? substr($code, 0, 120) : '[non-string]';
        error_log($preview);
        return null;
    }
}
