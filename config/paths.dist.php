<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

$root = '/var/www/pu239/';

return [
    'paths' => [
        'root' => $root,
        'include' => $root . 'include/',
        'public' => $root . 'public/',
        'storage' => $root . 'storage/',
    ],
];
