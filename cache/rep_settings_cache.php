<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);
global $CURUSER;

$GVARS = [
    'rep_is_online' => 1,
    'rep_default' => 10,
    'rep_undefined' => 'is off the scale',
    'rep_userrates' => 5,
    'rep_adminpower' => 5,
    'rep_rdpower' => 365,
    'rep_pcpower' => 1000,
    'rep_kppower' => 100,
    'rep_minpost' => 50,
    'rep_minrep' => 10,
    'rep_maxperday' => 10,
    'rep_repeat' => 20,
    'g_rep_negative' => true,
    'g_rep_seeown' => true,
    'g_rep_use' => $CURUSER['class'] > UC_MIN ? true : false,
];
