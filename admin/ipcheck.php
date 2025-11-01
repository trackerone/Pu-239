<?php
declare(strict_types=1);

use PU239\Admin\Controllers\IpcheckController;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
if (!isset($container)) {
    // TODO(2025): remove fallback once admin bootstrap wires controllers
    $container = require __DIR__ . '/../bootstrap/admin-container.php';
}

$controller = $container->get(IpcheckController::class);
$controller([]);
