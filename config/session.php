<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

$secureFlag = filter_var(getenv('SESSION_COOKIE_SECURE'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$httpOnlyFlag = filter_var(getenv('SESSION_COOKIE_HTTPONLY'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$useCookiesFlag = filter_var(getenv('SESSION_USE_COOKIES'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$useOnlyCookiesFlag = filter_var(getenv('SESSION_USE_ONLY_COOKIES'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$useStrictFlag = filter_var(getenv('SESSION_USE_STRICT_MODE'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$lazyWriteFlag = filter_var(getenv('SESSION_LAZY_WRITE'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

return [
    'session' => [
        'name' => getenv('SESSION_NAME') ?: 'PU239SESSID',
        'handler' => getenv('SESSION_SAVE_HANDLER') ?: 'files',
        'cookie_domain' => getenv('SESSION_COOKIE_DOMAIN') ?: '',
        'cookie_path' => getenv('SESSION_COOKIE_PATH') ?: '/',
        'cookie_secure' => $secureFlag ?? false,
        'cookie_httponly' => $httpOnlyFlag ?? true,
        'use_cookies' => $useCookiesFlag ?? true,
        'use_only_cookies' => $useOnlyCookiesFlag ?? true,
        'use_strict_mode' => $useStrictFlag ?? true,
        'use_trans_sid' => false,
        'sid_length' => (int) (getenv('SESSION_SID_LENGTH') ?: 128),
        'lazy_write' => $lazyWriteFlag ?? false,
        'gc_maxlifetime' => (int) (getenv('SESSION_GC_MAXLIFETIME') ?: 1440),
        'start_mode' => getenv('SESSION_START_MODE') ?: 'Strict',
        'ini' => [
            'memory_limit' => getenv('PHP_MEMORY_LIMIT') ?: '1024M',
            'zlib.output_compression' => 'Off',
            'display_errors' => getenv('PHP_DISPLAY_ERRORS') ?: 'Off',
            'log_errors' => getenv('PHP_LOG_ERRORS') ?: 'On',
            'ignore_repeated_errors' => 'On',
            'error_log' => getenv('PHP_ERROR_LOG') ?: '',
            'default_charset' => getenv('DEFAULT_CHARSET') ?: 'utf-8',
            'max_execution_time' => (int) (getenv('PHP_MAX_EXECUTION_TIME') ?: 300),
        ],
    ],
];
