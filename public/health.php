<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}

$appConfig = [];
$configFiles = [
    __DIR__ . '/../config/app.php',
    __DIR__ . '/../config/app.dist.php',
];

foreach ($configFiles as $configFile) {
    if (!is_file($configFile)) {
        continue;
    }

    /** @var array<string,mixed> $values */
    $values = require $configFile;
    if (is_array($values)) {
        $appConfig = array_replace_recursive($appConfig, $values);
    }
}

$appName = (string) ($appConfig['name'] ?? getenv('APP_NAME') ?: 'Pu-239');
$appVersion = $appConfig['version'] ?? null;

if ($appVersion !== null) {
    $appVersion = (string) $appVersion;
}

echo json_encode([
    'status' => 'ok',
    'app' => $appName,
    'php' => PHP_VERSION,
    'app_version' => $appVersion,
], JSON_THROW_ON_ERROR);
