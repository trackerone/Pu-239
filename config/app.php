<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

$environment = getenv('APP_ENV') ?: 'local';
$debugFlag = filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$debug = $debugFlag ?? ($environment !== 'prod' && $environment !== 'production');
$productionFlag = filter_var(getenv('APP_PRODUCTION'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$production = $productionFlag ?? ($environment === 'prod' || $environment === 'production');

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'Pu-239',
        'url' => getenv('APP_URL') ?: 'https://pu239.example',
        'environment' => $environment,
        'debug' => $debug,
        'production' => $production,
        'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
        'locale' => getenv('APP_LOCALE') ?: 'en_US',
        'log_channel' => getenv('APP_LOG_CHANNEL') ?: 'stack',
    ],
];
