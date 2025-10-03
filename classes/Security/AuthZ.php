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
                0 => 'user',
                1 => 'poweruser',
                2 => 'moderator',
                3 => 'staff',
                4 => 'admin',
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
            'user' => 1,
            'poweruser' => 2,
            'moderator' => 3,
            'staff' => 4,
            'admin' => 5,
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

// >>>>>> PU239:authz-helper-1
