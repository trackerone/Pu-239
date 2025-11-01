<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Admin\Controllers\FreeleechController;
use PU239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;

global $container;
/** @var ContainerInterface $container */
if (!$container instanceof ContainerInterface) {
    throw new \RuntimeException('Container not initialized');
}

$controller = new FreeleechController(
    $container,
    $container->get(ConfigRepository::class),
);
$controller();
