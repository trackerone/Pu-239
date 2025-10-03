<?php
declare(strict_types=1);

namespace PU239\Http\Middlewares;

use PU239\Security\AuthZ;

final class AuthZGate
{
    /** @param string|array $requirement */
    public function __construct(private $requirement)
    {
    }

    public function process(callable $next)
    {
        $requirement = $this->requirement;
        if (is_array($requirement)) {
            if (isset($requirement['any']) && is_array($requirement['any'])) {
                AuthZ::requireAnyRole(array_map('strval', $requirement['any']));
            } elseif (isset($requirement['all']) && is_array($requirement['all'])) {
                foreach ($requirement['all'] as $role) {
                    AuthZ::requireRole((string) $role);
                }
            } elseif (isset($requirement['role'])) {
                AuthZ::requireRole((string) $requirement['role']);
            }
        } else {
            AuthZ::requireRole((string) $requirement);
        }

        return $next();
    }
}

// >>>>>> PU239:http-mw-5
