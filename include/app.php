<?php

declare(strict_types = 1);
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'define.php';
require_once CONFIG_DIR . 'classes.php';
require_once VENDOR_DIR . 'autoload.php';
require_once INCL_DIR . 'function_common.php';

use DI\ContainerBuilder;
use SlashTrace\SlashTrace;

date_default_timezone_set('UTC');

$production = PRODUCTION;
$builder = new ContainerBuilder();
if ($production) {
    $builder->enableCompilation(DI_CACHE_DIR);
}
$builder->addDefinitions(CONFIG_DIR . '/config.php');
$builder->addDefinitions(CONFIG_DIR . '/emoticons.php');
$builder->addDefinitions(CONFIG_DIR . '/subtitles.php');
$builder->addDefinitions(CONFIG_DIR . '/whereis.php');
$builder->addDefinitions(CONFIG_DIR . '/definitions.php');
$builder->useAutowiring(true);
$builder->useAnnotations(false);
try {
    $container = $builder->build();
} catch (Exception $e) {
    //TODO Logger;
    dd($e);
}

require_once CONFIG_DIR . 'session.php';
$container->get(SlashTrace::class);
