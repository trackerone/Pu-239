<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Admin\Controllers\AdduserController;
use Pu239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use PDO;

global $container;
/** @var ContainerInterface $container */
if (!$container instanceof ContainerInterface) {
    throw new \RuntimeException('Container not initialized');
}

$controller = new AdduserController(
    $container,
    $container->get(ConfigRepository::class),
    $container->get(PDO::class),
);
$controller();
