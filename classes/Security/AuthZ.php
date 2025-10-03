<?php
declare(strict_types=1);

namespace PU239\Security;

final class AuthZ
{
    /**
     * Resolve current user's role/class as a normalized string.
     * Implement best-effort mapping from existing globals/session.
     */
    public static function currentRole(): ?string
    {
        // Best-effort: $CURUSER['role'] or class map
        $role = null;
        if (isset($GLOBALS['CURUSER']['role'])) {
            $role = (string) $GLOBALS['CURUSER']['role'];
        } elseif (isset($GLOBALS['CURUSER']['class'])) {
            // Map legacy numeric class to role names (adjust as needed)
            $map = [
<<<<<< codex/enforce-centralized-authorization-checks-s6jwwl
                0 => 'user',
                1 => 'poweruser',
                2 => 'superuser',
                3 => 'vip',
                4 => 'moderator',
                5 => 'staff', 
                6 => 'admin', 
                7 => 'sysop', 
=======
                'USER' => 0,
                'POWER_USER' => 1,
                'SUPER_USER' => 2,
                'VIP' => 3,
                'MODERATOR' => 4,
                'STAFF' => 5,
                'ADMINISTRATOR' => 6,
                'SYSOP' => 7,
>>>>>> master
            ];
            $role = $map[(int) $GLOBALS['CURUSER']['class']] ?? 'user';
        }
        return $role ?: null;
    }

    public static function requireRole(string $required): void
    {
        $role = self::currentRole();
        if (!self::isAllowed($role, [$required])) {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    public static function requireAnyRole(array $required): void
    {
        $role = self::currentRole();
        if (!self::isAllowed($role, $required)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    private static function isAllowed(?string $role, array $required): bool
    {
        if ($role === null) {
            return false;
        }
        // Simple hierarchy: user < poweruser < moderator < staff < admin
        $rank = [
<<<<<< codex/enforce-centralized-authorization-checks-s6jwwl
            0 => 'user',
            1 => 'poweruser',
            2 => 'superuser',
            3 => 'vip',
            4 => 'moderator',
            5 => 'staff', 
            6 => 'admin', 
            7 => 'sysop', 
=======
            'USER' => 0,
            'POWER_USER' => 1,
            'SUPER_USER' => 2,
            'VIP' => 3,
            'MODERATOR' => 4,
            'STAFF' => 5,
            'ADMINISTRATOR' => 6,
            'SYSOP' => 7,
>>>>>> master
        ];
        $r = $rank[$role] ?? 0;
        // Allow if role matches any exact required OR outranks minimal required (if required given as single string)
        foreach ($required as $req) {
            $reqRank = $rank[$req] ?? 99; // unknown requirement → deny unless exact match exists elsewhere
            if ($role === $req || $r >= $reqRank) {
                return true;
            }
        }
        return false;
    }
}

<<<<<< codex/enforce-centralized-authorization-checks-s6jwwl
=======
<<<<<< codex/enforce-centralized-authorization-checks-vacoay
=======
>>>>>> master
>>>>>> master
