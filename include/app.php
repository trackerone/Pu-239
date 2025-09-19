<?php
declare(strict_types=1);

use Psr\Container\ContainerInterface;

if (!isset($container) || !$container instanceof ContainerInterface) {
    throw new RuntimeException('Container was not initialised. Ensure bootstrap_core.php has been loaded first.');
}

return $container;
