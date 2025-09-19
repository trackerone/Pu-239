<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

global $container;

$db = $container->get(Pu239\Database::class);

class_check(UC_MAX);

require_once VENDOR_DIR . 'amnuts/opcache-gui/index.php';

