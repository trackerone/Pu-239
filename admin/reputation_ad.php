<?php
declare(strict_types=1);

if (!defined('PU239_ROUTED')) {
    require_once __DIR__ . '/../public/index.php';

    return;
}

require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Psr\Container\ContainerInterface;

global $container;
/** @var ContainerInterface $container */
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
// AUTO_ADMIN_MEDIUM: 2025-10-23; tool=codex-admin-medium-sweep; rules=2025.10.23-admin-medium

if (strpos(__FILE__, '/admin/') !== false) {
    AuthZ::requireRole('admin');
} else {
    AuthZ::requireAnyRole(['staff', 'admin']);
}

throw new RuntimeException('Stubbed: missing SQL; see tools/rehydrate_v3_manifest.csv');
