<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Security\AuthZ;

global $container;

AuthZ::requireRole('admin');

$db = $container->get(Pu239\Database::class);

class_check(UC_MAX);

require_once VENDOR_DIR . 'amnuts/opcache-gui/index.php';

