<?php

declare(strict_types=1);


$db = $container->get(Database::class);
use Rector\Config\RectorConfig;
use Rector\Laravel\Set\LaravelSetList;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
    ]);

    $rectorConfig->parallel();
    $rectorConfig->import(LevelSetList::UP_TO_PHP_83);
    $rectorConfig->import(LaravelSetList::LARAVEL_110);
};
