#!/usr/bin/env php
<?php
declare(strict_types=1);

use Monolog\Logger;
use Pu239\Uglify\UglifyService;

require_once dirname(__DIR__) . '/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

# NOTE: set executable bit via git attributes/PR; online editor may not persist chmod.

require_once BIN_DIR . 'functions.php';
require_once INCL_DIR . 'function_html.php';

global $container;
/** @var Logger $logger */
$logger = $container->get(Logger::class);
$service = new UglifyService($logger);

$args = $argv ?? [];
array_shift($args);

$normalized = [];
foreach ($args as $arg) {
    $value = (string) $arg;
    if ($value === '') {
        continue;
    }
    if ($value === '-h' || $value === '--help') {
        fwrite(STDOUT, "Usage: bin/uglify.php [--update] [--classes] [--fix] [--all]\n");
        exit(0);
    }
    $value = ltrim($value);
    $value = ltrim($value, '-');
    if ($value !== '') {
        $normalized[] = $value;
    }
}

$allowed = ['all', 'update', 'classes', 'fix'];
$unknown = array_diff($normalized, $allowed);
if (!empty($unknown)) {
    fwrite(STDERR, 'Unknown option(s): ' . implode(', ', $unknown) . PHP_EOL);
    fwrite(STDERR, "Usage: bin/uglify.php [--update] [--classes] [--fix] [--all]\n");
    exit(2);
}

$toggleActive = false;
try {
    toggle_site_status(true);
    $toggleActive = true;
    $result = $service->run($normalized);
} catch (Throwable $throwable) {
    if ($toggleActive) {
        toggle_site_status(false);
    }
    fwrite(STDERR, '[EXCEPTION] ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}

toggle_site_status(false);

foreach ($result['messages'] as $message) {
    fwrite(STDOUT, $message . PHP_EOL);
}

if (!($result['ok'] ?? false)) {
    $errors = $result['errors'] ?? [];
    if (empty($errors)) {
        $errors = ['Uglify pipeline failed'];
    }
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}

echo "OK\n";
exit(0);
