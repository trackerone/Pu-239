<?php
declare(strict_types=1);

if (!defined('PU239_ROUTED')) {
    require_once __DIR__ . '/../public/index.php';

    return;
}

require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Security\AuthZ;

AuthZ::requireRole('admin');

throw new RuntimeException('Stubbed: missing SQL; see tools/rehydrate_v3_manifest.csv');
