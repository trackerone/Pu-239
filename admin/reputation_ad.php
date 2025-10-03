<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Security\AuthZ;

if (strpos(__FILE__, '/admin/') !== false) {
    AuthZ::requireRole('admin');
} else {
    AuthZ::requireAnyRole(['staff', 'admin']);
}
<<<<<< codex/enforce-centralized-authorization-checks-s6jwwl
=======
<<<<<< codex/enforce-centralized-authorization-checks-vacoay
=======

>>>>>> master
>>>>>> master

throw new RuntimeException('Stubbed: missing SQL; see tools/rehydrate_v3_manifest.csv');
