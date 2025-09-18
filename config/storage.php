<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

$bucketAllowedFlag = filter_var(getenv('BUCKET_ALLOWED'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

return [
    'storage' => [
        'bucket' => [
            'allowed' => $bucketAllowedFlag ?? true,
            'max_size' => (int) (getenv('BUCKET_MAX_SIZE') ?: 5242880),
        ],
        'filesystem' => [
            'path' => getenv('FILESYSTEM_STORAGE_PATH') ?: (realpath(__DIR__ . '/../storage') ?: (__DIR__ . '/../storage')),
        ],
    ],
    'webserver' => [
        'user' => getenv('WEBSERVER_USER') ?: 'www-data',
    ],
];
