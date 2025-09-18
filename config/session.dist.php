<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

return [
    'session' => [
        'name' => 'PU239SESSID',
        'handler' => 'files',
        'cookie_domain' => '',
        'cookie_path' => '/',
        'cookie_secure' => false,
        'cookie_httponly' => true,
        'use_cookies' => true,
        'use_only_cookies' => true,
        'use_strict_mode' => true,
        'use_trans_sid' => false,
        'sid_length' => 128,
        'lazy_write' => false,
        'gc_maxlifetime' => 1440,
        'start_mode' => 'Strict',
        'ini' => [
            'memory_limit' => '1024M',
            'zlib.output_compression' => 'Off',
            'display_errors' => 'Off',
            'log_errors' => 'On',
            'ignore_repeated_errors' => 'On',
            'error_log' => '',
            'default_charset' => 'utf-8',
            'max_execution_time' => 300,
        ],
    ],
];
