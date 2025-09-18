<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

$available = getenv('APP_AVAILABLE_LANGUAGES');
$availableLanguages = $available !== false && $available !== null && $available !== ''
    ? array_filter(array_map('trim', explode(',', $available)))
    : ['en_US'];

return [
    'language' => [
        'imdb' => getenv('LANGUAGE_IMDB') ?: 'en-US',
        'available' => $availableLanguages,
    ],
];
