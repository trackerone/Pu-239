<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

return [
    'services' => [
        'google_books' => [
            'api_key' => getenv('GOOGLE_BOOKS_KEY') ?: null,
            'country' => getenv('GOOGLE_BOOKS_COUNTRY') ?: 'US',
        ],
    ],
];
