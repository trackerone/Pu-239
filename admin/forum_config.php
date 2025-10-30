<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use PU239\Admin\Controllers\ForumConfigController;
use Pu239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;

global $container;
/** @var ContainerInterface $container */
if (!$container instanceof ContainerInterface) {
    throw new \RuntimeException('Container not initialized');
}

$controller = new ForumConfigController($container, $container->get(ConfigRepository::class));
$controller();
