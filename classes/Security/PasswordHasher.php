<?php
declare(strict_types=1);

namespace PU239\Security;

final class PasswordHasher
{
    private static function pepper(): string
    {
        return (string) (getenv('PWD_PEPPER') ?: '');
    }

    public static function hash(string $plain): string
    {
        self::assertPolicy($plain);
        $pepper = self::pepper();
        $pwd = $pepper !== '' ? $plain . $pepper : $plain;

        $opts = [
            'memory_cost' => (int) (getenv('PWD_ARGON_MEMORY') ?: (1 << 17)),
            'time_cost'   => (int) (getenv('PWD_ARGON_TIME')   ?: 4),
            'threads'     => (int) (getenv('PWD_ARGON_THREADS')?: 1),
        ];
        $hash = password_hash($pwd, PASSWORD_ARGON2ID, $opts);
        if ($hash === false) {
            throw new \RuntimeException('Unable to hash password.');
        }
        return $hash;
    }

    public static function assertPolicy(string $plain): void
    {
        $min = (int) (getenv('PWD_MIN_LEN') ?: 12);
        if (strlen($plain) < $min) {
            throw new \InvalidArgumentException('Password too short (min '.$min.').');
        }
        $classes = 0;
        $classes += (int) (bool) preg_match('/[a-z]/', $plain);
        $classes += (int) (bool) preg_match('/[A-Z]/', $plain);
        $classes += (int) (bool) preg_match('/\d/', $plain);
        $classes += (int) (bool) preg_match('/[^A-Za-z0-9]/', $plain);
        if ($classes < 3) {
            throw new \InvalidArgumentException('Password must include 3 of: lower, upper, digit, special.');
        }
    }
}

// >>>>>> PU239:pwdlight-helper-1
