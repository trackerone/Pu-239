<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Psr\Container\ContainerInterface;
use Pu239\Database;

global $container;
/** @var ContainerInterface $container */
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
// AUTO_ADMIN_MEDIUM: 2025-10-23; tool=codex-admin-medium-sweep; rules=2025.10.23-admin-medium

AuthZ::requireRole('admin');

$db = $container->get(Database::class);

class_check(UC_MAX);

require_once VENDOR_DIR . 'amnuts/opcache-gui/index.php';

