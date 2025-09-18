<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

return [
    'system' => [
        'mysqldump' => getenv('MYSQLDUMP_PATH') ?: '/usr/bin/mysqldump',
        'gzip' => getenv('GZIP_PATH') ?: '/bin/gzip',
    ],
];
