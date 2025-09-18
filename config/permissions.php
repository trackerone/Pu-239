<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

return [
    'permissions' => [
        'masks' => [
            'PERMS_NO_IP' => 0x1,
            'PERMS_BYPASS_BAN' => 0x2,
            'UNLOCK_MORE_MOODS' => 0x4,
            'PERMS_STEALTH' => 0x8,
        ],
    ],
];
